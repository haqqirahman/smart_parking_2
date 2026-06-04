import json
from datetime import datetime, timedelta
import pymysql
import numpy as np

# =========================
# KONFIGURASI DATABASE
# =========================
DB_CONFIG = {
    "host": "localhost",
    "user": "root",
    "password": "",
    "database": "car_park_db",
    "port": 3306,
}


def get_conn():
    return pymysql.connect(
        cursorclass=pymysql.cursors.DictCursor,
        **DB_CONFIG,
    )


def usage_per_hour(conn):
    """
    Hitung jumlah perubahan status parkir per jam
    (7 hari terakhir) dari tabel parking_history.
    """
    sql = """
        SELECT HOUR(event_time) AS h, COUNT(*) AS cnt
        FROM parking_history
        WHERE event_time >= NOW() - INTERVAL 7 DAY
        GROUP BY HOUR(event_time)
        ORDER BY h
    """
    arr = [0] * 24
    with conn.cursor() as cur:
        cur.execute(sql)
        for r in cur.fetchall():
            arr[int(r["h"])] = int(r["cnt"])
    return arr


def avg_duration_per_slot(conn):
    """
    Hitung rata-rata durasi parkir per slot (dalam menit)
    berdasarkan transisi status 'Occupied' -> status lain.
    Ambil 10 slot dengan durasi rata-rata terlama.
    """
    sql = """
        SELECT slot_number, status, event_time
        FROM parking_history
        ORDER BY slot_number, event_time
    """
    with conn.cursor() as cur:
        cur.execute(sql)
        rows = cur.fetchall()

    start_occ = {}
    durations = {}

    for r in rows:
        slot = str(r["slot_number"])
        st = (r["status"] or "").lower()
        ts = r["event_time"]

        if st.startswith("occ"):
            # mulai terisi
            start_occ[slot] = ts
        else:
            # status bukan occupied → hitung durasi kalau sebelumnya occupied
            if slot in start_occ:
                diff = (ts - start_occ[slot]).total_seconds() / 60
                if diff >= 0:
                    durations.setdefault(slot, []).append(diff)
                start_occ.pop(slot)

    # rata-rata per slot
    avg_per_slot = {
        s: sum(v) / len(v)
        for s, v in durations.items()
    }

    # ambil 10 terbesar
    sorted_items = sorted(
        avg_per_slot.items(),
        key=lambda x: x[1],
        reverse=True
    )[:10]

    return {
        "slots": [s for s, _ in sorted_items],
        "avg_minutes": [round(m, 1) for _, m in sorted_items]
    }


def occupancy_pie(conn):
    """
    Ambil status terakhir tiap slot untuk pie chart
    (occupied / free / other).
    """
    sql = """
        SELECT slot_number,
               SUBSTRING_INDEX(
                 GROUP_CONCAT(status ORDER BY event_time DESC),
                 ',', 1
               ) AS last_status
        FROM parking_history
        GROUP BY slot_number
    """

    occ = free = other = 0

    with conn.cursor() as cur:
        cur.execute(sql)
        for r in cur.fetchall():
            st = (r["last_status"] or "").lower()
            if st.startswith("occ"):
                occ += 1
            elif st.startswith("free") or st.startswith("empty"):
                free += 1
            else:
                other += 1

    return {"occupied": occ, "free": free, "other": other}


def heatmap_busy(conn):
    """
    Buat matriks 7×24 (hari × jam) jumlah event 'Occupied'
    7 hari terakhir, untuk heatmap kepadatan parkir.
    """
    sql = """
        SELECT DAYOFWEEK(event_time) AS dw,
               HOUR(event_time) AS h,
               COUNT(*) AS cnt
        FROM parking_history
        WHERE event_time >= NOW() - INTERVAL 7 DAY
          AND status = 'Occupied'
        GROUP BY DAYOFWEEK(event_time), HOUR(event_time)
    """

    heat = np.zeros((7, 24), dtype=int)

    with conn.cursor() as cur:
        cur.execute(sql)
        for r in cur.fetchall():
            # DAYOFWEEK: 1=Sunday ... 7=Saturday → ubah ke 0..6
            day = (int(r["dw"]) - 1) % 7
            hour = int(r["h"])
            heat[day][hour] = int(r["cnt"])

    return heat.tolist()


def main():
    try:
        conn = get_conn()
    except Exception as e:
        # kalau gagal konek DB, kirim error dalam bentuk JSON
        print(json.dumps({"error": str(e)}))
        return

    data = {
        "usage_per_hour": usage_per_hour(conn),
        "avg_duration": avg_duration_per_slot(conn),
        "occupancy_pie": occupancy_pie(conn),
        "heatmap": heatmap_busy(conn)
    }

    conn.close()

    # dashboard.php akan membaca output JSON ini
    print(json.dumps(data))


if __name__ == "__main__":
    main()
