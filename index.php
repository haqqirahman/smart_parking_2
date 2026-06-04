<!DOCTYPE html>
<html>
   <head>
    <!-- SweetAlert2 CSS & JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

      <!-- basic -->
      <meta charset="utf-8">
      <meta http-equiv="X-UA-Compatible" content="IE=edge">
      <meta name="viewport" content="width=device-width, initial-scale=1">
      <!-- mobile metas -->
      <meta name="viewport" content="width=device-width, initial-scale=1">
      <meta name="viewport" content="initial-scale=1, maximum-scale=1">
      <!-- site metas -->
      <title>SMART PARKING</title>
      <meta name="keywords" content="">
      <meta name="description" content="">
      <meta name="author" content="">
      <!-- bootstrap css -->
      <link rel="stylesheet" type="text/css" href="css/bootstrap.min.css">
      <!-- style css -->
      <link rel="stylesheet" type="text/css" href="css/style.css">
      <!-- Responsive-->
      <link rel="stylesheet" href="css/responsive.css">
      <!-- fevicon -->
      <link rel="icon" href="images/fevicon.png" type="image/gif" />
      <!-- font css -->
      <link href="https://fonts.googleapis.com/css?family=Poppins:400,500,600,700&display=swap" rel="stylesheet">
      <!-- Scrollbar Custom CSS -->
      <link rel="stylesheet" href="css/jquery.mCustomScrollbar.min.css">
      <!-- Tweaks for older IEs-->
      <link rel="stylesheet" href="https://netdna.bootstrapcdn.com/font-awesome/4.0.3/css/font-awesome.css">
      <!-- font awesome css -->
      <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
   </head>
   <body>
      <!-- header section strats -->
      <header class="header_section">
        <div class="container">
            <nav class="navbar navbar-expand-lg custom_nav-container ">

                <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
                    <span class=""> </span>
                </button>

                <div class="collapse navbar-collapse" id="navbarSupportedContent">
                   <ul class="navbar-nav">
                        <li class="nav-item">
                            <a class="nav-link" href="#home">Home</a>
                        </li>   
                        <li class="nav-item">
                            <a class="nav-link" href="#about">About</a>
                        </li>
                        <li class="nav-item"> <a class="nav-link navbar-brand" href=""> <span><span>SMART</span> PARKING</span></a> </li>
                        <li class="nav-item active">
                            <a class="nav-link" href="#feature">Feature</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#contact">Contact</a>
                        </li>
                    </ul>
                </div>
            </nav>
         </div>
      </header>
      <!-- end header section -->
      <!-- banner section start --> 
      <div id="home" class="banner_section layout_padding">
         <div class="container-fluid">
            <div class="row">
               <div class="col-md-6">
                  <div id="banner_slider" class="carousel slide" data-ride="carousel">
                     <div class="carousel-inner">
                        <div class="carousel-item active">
                           <div class="banner_taital_main">
                              <h1 class="banner_taital">Smart<br>Parking System</h1>
                              <p class="banner_text">A modern parking solution powered by smart sensors and cameras to monitor parking availability in real-time.</p>
                              <h1 class="banner_taital">----------------------</h1>
                                <div class="read_btn">
                                    <a href="login.php" class="btn btn-warning" >Login</a>
                                </div>
                           </div>
                        </div>
                        <div class="carousel-item">
                           <div class="banner_taital_main">
                              <h1 class="banner_taital">Intelligent Parking<br>Simplified</h1>
                              <p class="banner_text">Experience seamless parking management with AI-driven monitoring, automatic detection, and real-time updates.</p>
                              <h1 class="banner_taital">----------------------</h1>
                           </div>
                        </div>
                        <div class="carousel-item">
                           <div class="banner_taital_main">
                              <h1 class="banner_taital">Find Your Spot,<br>Fast</h1>
                              <p class="banner_text">Say goodbye to endless searching — our smart system helps you locate available parking instantly and efficiently.</p>
                              <h1 class="banner_taital">----------------------</h1>
                           </div>
                        </div>
                     </div>
                     <a class="carousel-control-prev" href="#banner_slider" role="button" data-slide="prev">
                     <i class="fa fa-angle-left"></i>
                     </a>
                     <a class="carousel-control-next" href="#banner_slider" role="button" data-slide="next">
                     <i class="fa fa-angle-right"></i>
                     </a>
                  </div>
               </div>
               <div class="col-md-6 padding_right0">
                  <div class="banner_img"><img src="images/banner-img.png"></div>
               </div>
            </div>
         </div>
      </div>
      <!-- banner section end -->
      <!-- about section start -->
      <div id="about" class="about_section layout_padding">
         <div class="container-fluid">
            <div class="row">
               <div class="col-md-6">
                  <div class="about_img"><img src="images/about-img.png"></div>
               </div>
               <div class="col-md-6">
                    <h3 class="about_taital">About Smart Parking System</h3>
                    <p class="about_text">The Smart Parking System is designed to optimize parking management using modern technology. With integrated sensors and cameras, it provides real-time information about available parking spaces, reducing congestion and saving time for drivers. This system utilizes IoT-based detection and intelligent monitoring to automatically identify occupied and vacant slots. The collected data is processed and displayed to users and administrators, allowing for accurate decision-making and improved traffic flow within parking areas.</p>
                    <p class="about_text" style="font-weight:bold;">
                    Our goal is to make parking smarter, faster, and more efficient for everyone—enhancing user experience while supporting sustainable and modern urban mobility.
                    </p>

               </div>
            </div>
         </div>
      </div>
      <!-- about section end -->
      <!-- models section start -->
      <div id="feature" class="models_section layout_padding">
         <div class="container">
            <div class="row">
               <div class="col-md-12">
                  <h1 class="models_taital">Our System Features</h1>
               </div>
            </div>
            <div class="models_section_2">
               <div class="row">
                  <div class="col-md-6">
                     <div class="models_img"><img src="images/img-1.png"></div>
                  </div>
                  <div class="col-md-6">
                     <h3 class="carolo_text"><span class="number_text">01</span> Real-Time Parking Detection</h3>
                     <p class="ullamco_text">Our system uses intelligent IoT-based sensors and high-resolution cameras to detect the presence of vehicles in each parking slot. Real-time data is instantly sent to the central server, ensuring accurate and up-to-date information for drivers. This technology helps users find parking faster and improves the overall efficiency of the parking area.</p>
                  </div>
               </div>
            </div>
            <div class="models_section_2">
               <div class="row">
                  <div class="col-md-6">
                     <h3 class="carolo_text"><span class="number_text">02</span> Smart Monitoring Dashboard</h3>
                     <p class="ullamco_text">The Smart Monitoring Dashboard allows administrators to monitor all parking activities through an intuitive and user-friendly interface. It displays live data such as the number of available spots, vehicle movement, and system alerts. This central visibility ensures smooth operations and effective parking management at all times.</p>
                  </div>
                  <div class="col-md-6">
                     <div class="models_img"><img src="images/img-2.png"></div>
                  </div>
               </div>
            </div>
            <div class="models_section_2">
               <div class="row">
                  <div class="col-md-6">
                     <div class="models_img"><img src="images/img-3.png"></div>
                  </div>
                  <div class="col-md-6">
                     <h3 class="carolo_text"><span class="number_text">03</span> Seamless User Experience</h3>
                     <p class="ullamco_text">Designed for convenience, the Smart Parking System provides a smooth and efficient parking process. Drivers can easily locate available spaces through the display or web interface before entering the parking area. Real-time updates reduce waiting time and congestion, creating a more organized and stress-free parking experience.</p>
                  </div>
               </div>
            </div>
         </div>
      </div>
      <!-- models section end -->
      <div class="choose_section_2">
            <div class="container">
               <div class="row">
                <div class="col-lg-3 col-sm-6">
                <h1 class="rated_text"><span class="padding_10"><img src="images/icon-1.png"></span>50+</h1>
                <p class="house_text">Active Parking Spots</p>
                </div>
                <div class="col-lg-3 col-sm-6">
                <h1 class="rated_text"><span class="padding_10"><img src="images/icon-2.png"></span>95%</h1>
                <p class="house_text">Detection Accuracy</p>
                </div>
                <div class="col-lg-3 col-sm-6">
                <h1 class="rated_text"><span class="padding_10"><img src="images/icon-3.png"></span>24/7</h1>
                <p class="house_text">Real-Time Monitoring</p>
                </div>
                <div class="col-lg-3 col-sm-6">
                <h1 class="rated_text"><span class="padding_10"><img src="images/icon-4.png"></span>500+</h1>
                <p class="house_text">Registered Users</p>
                </div>

               </div>
            </div>
         </div>
      <!-- client section start -->
      <div class="client_section layout_padding">
         <div class="container">
            <div class="row">
               <div class="col-md-12">
                  <h1 class="client_taital">What Says Our Students</h1>
                    <p class="client_text">See how our Smart Parking System helps users and administrators every day.</p>
               </div>
            </div>
            <div class="customer_section_2">
               <div class="container">
                  <div class="row">
                     <div class="col-md-12">
                        <div class="box_main">
                           <div class="customer_main">
                              <div class="customer_left">
                                 <div class="customer_img"><img src="images/client-img.png"></div>
                              </div>
                              <div class="customer_right">
                                 <h3 class="customer_name">Someone</h3>
                                    <p class="enim_text">
                                    The Smart Parking System has made parking management more efficient and reliable. The real-time monitoring feature ensures accurate information about available spaces, helping reduce congestion and saving time for drivers.
                                    </p>
                                 <div class="quick_icon"><img src="images/quick-icon.png"></div>
                              </div>
                           </div>
                        </div>
                     </div>
                  </div>
               </div>
            </div>
         </div>
      </div>
      <!-- client section end -->
      <!-- contact section start -->
      <div id="contact" class="contact_section layout_padding">
         <div class="container">
            <div class="row">
               <div class="col-md-12">
                  <h1 class="contact_taital">Get In Touch</h1>
               </div>
            </div>
            <div class="contact_section_2">
               <div class="mail_section map_form_container">
                  <form id="contactForm" action="send_contact.php" method="post">
                    <input type="text" class="mail_text" placeholder="Name" name="Name" required>
                    <input type="text" class="mail_text" placeholder="Phone Number" name="PhoneNumber" required>
                    <input type="email" class="mail_text" placeholder="Email" name="Email" required>
                    <textarea class="massage-bt" placeholder="Message" rows="5" id="comment" name="Message" required></textarea>

                  <div class="map_btn_main">
                     <div class="send_bt"><a href="#" id="sendNowLink">Send Now</a></div>
                     <div class="map_bt">
                        <a href="https://share.google/6LMmB8l8GCWZZZBit" target="_blank">Map</a>
                    </div>
                  </div>
                  </form>
                  <div class="map_main map_container">
                     <div class="map-responsive">
                        <iframe src="https://www.google.com/maps/embed/v1/place?key=AIzaSyA0s1a7phLN0iaD6-UE7m4qP-z21pH0eSc&amp;q=Eiffel+Tower+Paris+France" width="600" height="368" frameborder="0" style="border:0; width: 100%;" allowfullscreen=""></iframe>
                         <div class="map_btn_main">
                           <div class="map_bt d-flex justify-content-center w-100 map_center"><a href="#" id="showForm">Form</a></div>
                        </div>
                     </div>
                  </div>
               </div>
            </div>
            <div class="location_main">
               <div class="location_text">
                  <ul>
                     <li><a href="#"><i class="fa fa-phone" aria-hidden="true"></i><span class="padding_left_15">(+62) 88210414425</span></a></li>
                     <li><a href="#"><i class="fa fa-envelope" aria-hidden="true"></i><span class="padding_left_15">caturanakkartika5@gmail.com</span></a></li>
                  </ul>
               </div>
            </div>
            <div class="social_icon">
                <ul>
                    <li><a href="https://x.com/Ralfaaaa_" target="_blank"><i class="fa fa-twitter" aria-hidden="true"></i></a></li>
                    <li><a href="https://www.linkedin.com/in/catur-sastro-utomo-70226129b/" target="_blank"><i class="fa fa-linkedin" aria-hidden="true"></i></a></li>
                    <li><a href="https://www.instagram.com/ralfadzila" target="_blank"><i class="fa fa-instagram" aria-hidden="true"></i></a></li>
                </ul>
            </div>

         </div>
      </div>
      <!-- contact section end -->
      <!-- copyright section start -->
      <div class="copyright_section">
         <div class="container">
            <p class="copyright_text">
                &copy; 2025 Smart Parking System. All Rights Reserved.
            </p>
        </div>

      </div>
      <!-- copyright section end -->
      <!-- Javascript files-->
      <script src="js/jquery.min.js"></script>
      <script src="js/popper.min.js"></script>
      <script src="js/bootstrap.bundle.min.js"></script>
      <script src="js/jquery-3.0.0.min.js"></script>
      <script src="js/plugin.js"></script>
      <!-- sidebar -->
      <script src="js/jquery.mCustomScrollbar.concat.min.js"></script>
      <script src="js/custom.js"></script>
   </body>
</html>


<script>
document.getElementById('sendNowLink').addEventListener('click', function(e) {
    e.preventDefault(); // cegah link default

    const form = document.getElementById('contactForm');
    const formData = new FormData(form);

    fetch('send_contact.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.text())
    .then(data => {
        if(data.trim() === 'success'){
            Swal.fire({
                icon: 'success',
                title: 'Message Sent!',
                text: 'Thank you for contacting us. We will get back to you soon.',
                confirmButtonColor: '#fc9d22'
            });
            form.reset(); // reset form
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Failed to Send',
                text: 'There was a problem sending your message. Please try again later.',
                confirmButtonColor: '#fc9d22'
            });
        }
    })
    .catch(error => {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Something went wrong. Check your connection.',
            confirmButtonColor: '#fc9d22'
        });
        console.error(error);
    });
});
</script>

<script>
document.querySelectorAll('a.nav-link').forEach(anchor => {
    anchor.addEventListener('click', function(e) {
        e.preventDefault();
        const targetID = this.getAttribute('href').substring(1); // hapus #
        const targetSection = document.getElementById(targetID);
        if(targetSection) {
            window.scrollTo({
                top: targetSection.offsetTop + 30, // jika navbar fixed, beri offset
                behavior: 'smooth'
            });
        }
    });
});
</script>

