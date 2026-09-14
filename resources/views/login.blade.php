<!DOCTYPE html>
<html lang="en">

<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<meta name="description" content="A brief description of your website for SEO purposes.">
<meta name="keywords" content="your, keywords, here, separated, by, commas">
<meta name="author" content="Your Name or Company">
<meta property="og:title" content="Your Website Title">
<meta property="og:description" content="Your website description for social media sharing">
<meta property="og:image" content="URL_to_image">
<meta property="og:url" content="Your Website URL">
<title>Your Website Title</title>

<!-- Open Graph for better social media sharing -->
<meta property="og:type" content="website">

<!-- Twitter Card metadata -->
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="Your Website Title">
<meta name="twitter:description" content="A brief description of your site for Twitter sharing">
<meta name="twitter:image" content="URL_to_image">

<!-- Favicon -->
<link rel="icon" href="images/favicon.ico" type="image/x-icon">

<!-- Google font -->
<link rel="preconnect" href="https://fonts.googleapis.com/">
<link rel="preconnect" href="https://fonts.gstatic.com/" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,100..1000;1,9..40,100..1000&amp;display=swap" rel="stylesheet">


<!-- Style Sheets -->

<!-- Bootstrap Css -->
<link rel="stylesheet" href="css/bootstrap.min.css">
<!-- Bootstrap Icon Css -->
<link href="css/bootstrapicon.min.css" rel="stylesheet">
<!-- Remix Icon Css -->
<link href="css/remixicon.css" rel="stylesheet">
<!-- Swiper Slider Css -->
<link rel="stylesheet" href="css/swiperbundle.min.css">
<!-- Font Awesome Css -->
<link rel="stylesheet" href="css/fontawesome-all.min.css">
<!-- Main Css -->
<link rel="stylesheet" href="css/style.css">

<body>

<div class="login-page">
    <div class="container-fluid">
        <div class="row">
          <div class="col-lg-4">
            <div class="login-content">
              <div class="logo">
                <a href="index.html"><img src="images/logo.png" alt="logo" width="220px"></a>
                <span class="d-block">Your Hosting Our <label>Responsibility</label></span>
              </div>
              <div class="login-form text-center">
                <h4 class="mb-3">Secure Client Login</h4>
                <form>
                  <div class="col-12 text-start">
                    <label>Email Address</label>
                    <input class="inputs" type="text" placeholder="Enter email">
                  </div>
                  <div class="col-12 text-start mt-3">
                    <div class="pwrest">
                      <label>Password</label>
                      <a href="forgot-password.html" class="color-main">Forgot ?</a>
                    </div>
                    <input class="inputs" type="Password" placeholder="Enter email">
                  </div>
                  <div class="col-12 text-start mt-3">
                    <label for="checkbox" class="checkbox">
                    <input type="checkbox" name="rememberme" id="checkbox"> Remember Me</label>
                  </div>
                  <a class="btns one w-100 mt-3" href="#">Login Now <span><i class="bi bi-arrow-right px-2"></i></span></a>
                </form>
              </div>
              <div class="login_footer">
                <span>Not a member yet? <strong><a href="{{route('registar')}}" class="color-main">Sign Up</a></strong> <br>and get started now!</span>
              </div>
            </div>
          </div>
          <div class="col-lg-8 bg-primary">
            <div class="login-sidebar">
              <div class="row justify-content-center g-4 w-100">
                <div class="col-12">
                  <div class="section-header gap-bottom center">
                    <h2 class="text-white">Choose Your Flexible Pricing Plan </h2>
                    <p class="text-white">No commitment, no upfront costs – just 100% performance.</p>
                  </div>
                </div>
                <div class="col-lg-4">
                  <div class="feature-style-4 bordered text-center">
                    <div class="icon mb-2">
                      <img src="images/data-protection.png" alt="svg" width="56">
                    </div>
                    <h4>Reseller Hosting</h4>
                    <p>Secure, scalable cloud hosting for optimized website performance.</p>
                    <a class="btns one w-100" href="reseller-hosting.html">Buy Now</a>
                  </div>
                </div>
                <div class="col-lg-4">
                  <div class="feature-style-4 bordered text-center">
                    <div class="icon mb-2">
                      <img src="images/data-integrity.png" alt="svg" width="56">
                    </div>
                    <h4>VPS Hosting</h4>
                    <p>Secure, scalable cloud hosting for optimized website performance.</p>
                    <a class="btns one w-100" href="vps-server.html">Buy Now</a>
                  </div>
                </div>
                <div class="col-lg-4">
                  <div class="feature-style-4 bordered text-center">
                    <div class="icon mb-2">
                      <img src="images/data-storage.png" alt="svg" width="56">
                    </div>
                    <h4>Dedicated Hosting</h4>
                    <p>Secure, scalable cloud hosting for optimized website performance.</p>
                    <a class="btns one w-100" href="dedicated-server.html">Buy Now</a>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
</div>

<!-- jQuery v3.6.0 JS --> 
<script src="{{ URL('/assets1/js/jquery-3.6.0.min.js') }}"></script> 
<!-- Popper Bootstrap JS--> 
<script src="{{ URL('/assets1/js/popper.min.js') }}"></script> 
<!-- Swiper Slider JS--> 
<script src="{{ URL('/assets1/js/swiper-bundle.min.js') }}"></script> 
<!-- Bootstrap JS--> 
<script src="{{ URL('/assets1/js/bootstarp.min.js') }}"></script> 
<!-- Main JS--> 
<script src="{{ URL('/assets1/js/main.js') }}"></script>

<script type="module" src="https://static.cloudflareinsights.com/beacon.min.js/v31edd6df95cf4e85bb4c19e7a9bdbcba1788362987495" integrity="sha512-iIg7k2xntmwu6/uSb5tpc/hySgZc4eoL31yB29W6tJFo2akwjPWcEqnCEdJvGexCL0KEQwVYv5BlowfhVz26hg==" data-cf-beacon='{"version":"2024.11.0","token":"cf8dccc3f5f5450cacae0200ae4e8741","r":1,"spa":2}' crossorigin="anonymous"></script>
</body>

</html>