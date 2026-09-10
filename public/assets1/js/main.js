'use strict';
var theme = {
 
  init: function () {
    theme.scrollButton();
    theme.topbardropdown();
    theme.accordion();
    theme.mobileMenu();
    theme.scrollupHeader();
    theme.tabings();
    theme.webMenu();
    theme.tabbingbtn();
    theme.swiperslider();
    theme.vpsPlan();
    theme.barDraggable();
  },

  /**
   * Scroll to Top Button
   */
  scrollButton: function () {
    var $scrollTop = $('.scroll-top');
    if ($scrollTop.length) {
      var toggleScrollTop = function () {
        $(window).scrollTop() > 100 ? $scrollTop.addClass('active') : $scrollTop.removeClass('active');
      };
      $(window).on('load', toggleScrollTop);
      $(document).on('scroll', toggleScrollTop);
    }

    $("#scrolltoTop").click(function () {
      $("html").animate({ scrollTop: 0 }, "slow");
    });
  },
/**
   * topbar dropdown
   */
topbardropdown: function () {
  const dropdowns = document.querySelectorAll('.top-bar-dropdown');

  dropdowns.forEach(function (dropdown) {
    const button = dropdown.querySelector('.dropdown-btn');
    const menu = dropdown.querySelector('.dropdown-menus');

    button.addEventListener('click', function (e) {
      e.stopPropagation();
      menu.classList.toggle('open');
      dropdowns.forEach(function (otherDropdown) {
        if (otherDropdown !== dropdown) {
          otherDropdown.querySelector('.dropdown-menus').classList.remove('open');
        }
      });
    });
  });
  document.addEventListener('click', function (e) {
    if (!e.target.closest('.top-bar-dropdown')) {
      dropdowns.forEach(function (dropdown) {
        dropdown.querySelector('.dropdown-menus').classList.remove('open');
      });
    }
  });
}, 
  /**
   * Accordion
   */
  accordion: function () {
    $(".accordion > li:first").addClass("active").find("p").slideDown();
    $('.accordion li').on('click', function () {
      var dropDown = $(this).find("p");

      $(this).closest(".accordion").find("p").not(dropDown).slideUp();

      if ($(this).hasClass("active")) {
        $(this).removeClass("active");
      } else {
        $(this).closest(".accordion").find("li.active").removeClass("active");
        $(this).addClass("active");
      }

      dropDown.stop(false, true).slideToggle();
    });
  },

  /**
   * Mobile Menu
   */
  mobileMenu: function () {
    function popupMobileMenu() {
        if (window.matchMedia('(max-width: 991px)').matches) {
           
            $('.navbar-button').on('click', function () {
                if ($('.header-menu').hasClass('active')) {
                    closeMenu();
                } else {
                    $('.header-menu').addClass('active');
                    $(this).addClass('active');
                }
            });

            $('.navbar-close').on('click', function () {
                closeMenu();
            });

            $('.header-menu .mainmenu .has-droupdown > a').on('click', function (e) {
                e.preventDefault();
                $('.header-menu .mainmenu .has-droupdown > a').not(this).siblings('.submenu, .rn-megamenu').removeClass('active').slideUp('400');
                $('.header-menu .mainmenu .has-droupdown > a').not(this).removeClass('open');
                let $submenu = $(this).siblings('.submenu, .rn-megamenu');
                if ($submenu.hasClass('active')) {
                    $submenu.removeClass('active').slideUp('400');
                    $(this).removeClass('open');
                } else {
                    $submenu.addClass('active').slideDown('400');
                    $(this).addClass('open');
                }
            });

            
            $('.header-menu').on('click', function (e) {
                if (e.target === this) {
                    closeMenu();
                }
            });
        }
    }

    function closeMenu() {
        $('.header-menu').removeClass('active');
        $('.navbar-button').removeClass('active');
        $('.navbar-close').removeClass('active');
        $('.header-menu .mainmenu .has-droupdown > a').siblings('.submenu, .rn-megamenu').removeClass('active').slideUp('400');
        $('.header-menu .mainmenu .has-droupdown > a').removeClass('open');
    }

    popupMobileMenu();

    
    $(window).resize(function () {
        $('.navbar-button').off('click');
        $('.navbar-close').off('click');
        $('.header-menu .mainmenu .has-droupdown > a').off('click');
        $('.header-menu').off('click');

        popupMobileMenu();
    });
},

scrollupHeader: function () {
  var previousScroll = 0;
  var $header = $('header'); 

  $(window).on('scroll', function () {
      var currentScroll = $(this).scrollTop();

      if (currentScroll < previousScroll && currentScroll > 0) {
         
          $header.addClass('header-sticky');
      } else {
         
          $header.removeClass('header-sticky');
      }

      previousScroll = currentScroll;
  });
},


/**
   * Web Screen Menu
   */
webMenu: function () {
  function handleMenu() {
    if ($(window).width() >= 992) {
      $('.has-droupdown > a').off('click').on('click', function (e) {
        e.preventDefault();
        var $thisDropdown = $(this).parent('.has-droupdown');
        var $submenu = $thisDropdown.find('.submenu');
        
        
        $('.submenu').not($submenu).removeClass('visible');
        $('.has-droupdown').not($thisDropdown).removeClass('dropdown-opened');
        $('.main-header').removeClass('dropdown-open'); 
        
        
        $submenu.toggleClass('visible');
        $thisDropdown.toggleClass('dropdown-opened', $submenu.hasClass('visible'));
        
        
        if ($submenu.hasClass('visible')) {
          $('.main-header').addClass('dropdown-open');
        }
      });

      
      $(document).off('click').on('click', function (e) {
        if (!$(e.target).closest('.has-droupdown').length) {
          $('.submenu').removeClass('visible');
          $('.has-droupdown').removeClass('dropdown-opened');
          $('.main-header').removeClass('dropdown-open'); 
        }
      });
    } else {
    }
  }

  handleMenu();

  
  $(window).resize(function () {
    handleMenu();
  });
},
tabbingbtn: function () {
  const tabButtons = document.querySelectorAll(".tab-btn");
  const tabPanes = document.querySelectorAll(".tab-pane");

  tabButtons.forEach((button) => {
    button.addEventListener("click", function () {
      tabButtons.forEach((btn) => btn.classList.remove("active"));
      tabPanes.forEach((pane) => pane.classList.remove("active"));
      button.classList.add("active");
      const targetTab = button.getAttribute("data-tab");
      const targetPane = document.getElementById(targetTab);
      if (targetPane) {
        targetPane.classList.add("active");
      }
    });
  });
},
  //======< Tabing Items >======

  tabings: function () {
    $('.tabs .items .item').click(function () {
      $('.tabs .items .item').removeClass('active');
      $(this).addClass('active');
      var tabNumber = $(this).data('tab');
      $('.contents .item').removeClass('active');
      $('.contents .item[data-content="' + tabNumber + '"]').addClass('active');
    });
    $('.tabs .items .item').first().trigger('click');
  },

  swiperslider: function () {
    var homeBannerSwiper = new Swiper('.home-banner-swiper', {
      speed: 1000,
      loop: false,
      autoplay: {
          delay: 5000,
          disableOnInteraction: false
      },
      slidesPerView: 'auto',
      pagination: {
          el: '.swiper-pagination',
          type: 'bullets',
          clickable: true
      },
      breakpoints: {
          320: {
              slidesPerView: 1,
              spaceBetween: 0
          },
          992: {
              slidesPerView: 1,
              spaceBetween: 0
          }
      },
      on: {
          slideChange: function () {
              updateActiveDot(homeBannerSwiper.activeIndex);
          }
      }
  });

  var dotItems = document.querySelectorAll('.dot-slider .container ul li');

  
  dotItems.forEach((li, index) => {
      li.addEventListener('click', function () {
          homeBannerSwiper.slideTo(index);
      });
  });

  function updateActiveDot(activeIndex) {
      dotItems.forEach((li, index) => {
          if (index === activeIndex) {
              li.classList.add('active');
          } else {
              li.classList.remove('active');
          }
      });
  }

  
  updateActiveDot(homeBannerSwiper.activeIndex);
    new Swiper('.swiper-testimonial', {
      speed: 1000,
      loop: false,
      autoplay: {
        delay: 2500,
        disableOnInteraction: false
      },
      pagination: {
        el: '.swiper-pagination',
        type: 'bullets',
        clickable: true
      },
      slidesPerView: 'auto',
      breakpoints: {
        320: {
          slidesPerView: 1,
          spaceBetween: 20
        },
        575: {
          slidesPerView: 2,
          spaceBetween: 20
        },
        767: {
          slidesPerView: 3,
          spaceBetween: 20
        }
      }
    });
    new Swiper('.swiper-plans-two', {
      speed: 1000,
      loop: false,
      autoplay: {
        delay: 2500,
        disableOnInteraction: false
      },
      pagination: {
        el: '.swiper-pagination',
        type: 'bullets',
        clickable: true
      },
      slidesPerView: 'auto',
      breakpoints: {
        320: {
          slidesPerView: 1,
          spaceBetween: 20
        },
        575: {
          slidesPerView: 2,
          spaceBetween: 20
        },
        767: {
          slidesPerView: 3,
          spaceBetween: 20
        }
      }
    });
    new Swiper('.swiper-plans', {
      speed: 1000,
      loop: false,
      autoplay: {
        delay: 2500,
        disableOnInteraction: false
      },
      pagination: {
        el: '.swiper-pagination',
        type: 'bullets',
        clickable: true
      },
      
      slidesPerView: 'auto',
      breakpoints: {
        320: {
          slidesPerView: 1,
          spaceBetween: 20
        },
        575: {
          slidesPerView: 2,
          spaceBetween: 20
        },
        767: {
          slidesPerView: 3,
          spaceBetween: 20
        },
        991: {
          slidesPerView: 4,
          spaceBetween: 20
        }
      }
    });

    new Swiper('.swiper-tld', {
      speed: 1000,
      loop: false,
      autoplay: {
        delay: 2500,
        disableOnInteraction: false
      },
      pagination: {
        el: '.swiper-pagination',
        type: 'bullets',
        clickable: true
      },
      navigation: {
        nextEl: '.swiper-tld-right',
        prevEl: '.swiper-tld-left',
      },
      slidesPerView: 'auto',
      breakpoints: {
        320: {
          slidesPerView: 2,
          spaceBetween: 20
        },
        575: {
          slidesPerView: 4,
          spaceBetween: 20
        },
        767: {
          slidesPerView: 5,
          spaceBetween: 20
        },
        991: {
          slidesPerView: 6,
          spaceBetween: 20
        },
        1200: {
          slidesPerView: 7,
          spaceBetween: 20
        }
      }
    });
  },
/**
   * VPS Plan
   */
vpsPlan: function () {
  $('.plan-names > div').on('click', function () {
    theme.updateBar($(this).index());
  });

  
  theme.updateBar(0);
},

/**
 * Make bar draggable
 */
barDraggable: function () {
  var bar = $('.bar');
  var barIn = $('.bar-in');
  var plans = $('.plan-names > div');
  var totalPlans = plans.length;
  var step = (100 - 3) / (totalPlans - 1); 
  
  bar.on('mousedown', function (event) {
    var barWidth = bar.width();
    var startX = event.pageX;
    var startWidth = barIn.width();
    
    $(document).on('mousemove', function (event) {
      var diff = event.pageX - startX;
      var newWidth = Math.max(0, Math.min(barWidth, startWidth + diff));
      var percentage = (newWidth / barWidth) * 100;
      
      
      var adjustedPercentage = Math.min(100, Math.max(3, Math.round((percentage - 3) / step) * step + 3));
      barIn.css('width', adjustedPercentage + '%');
      
      
      var index = Math.round((adjustedPercentage - 3) / step);
      theme.updateBar(index);
    });
    
    $(document).on('mouseup', function () {
      $(document).off('mousemove mouseup');
    });
  });
},

/**
 * Update bar and corresponding elements
 */
updateBar: function (index) {
  var totalPlans = $('.plan-names > div').length;
  var step = (100 - 3) / (totalPlans - 1); 
  var widthPercentage = Math.min(100, index * step + 3) + '%';
  
 
  $('.bar-in').css('width', widthPercentage);
  
  
  $('.vps-plan-tab > .inner').hide().eq(index).show();
  
  $('.plan-names > div').removeClass('active').eq(index).addClass('active');
},

};

// Initialize the theme
document.addEventListener('DOMContentLoaded', function () {
  theme.init();
});