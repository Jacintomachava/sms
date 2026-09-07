(function () {
  const review_swiper_2 = new Swiper(".review-slider-2", {
    loop: true,
    spaceBetween: 5,
    autoplay: {
      delay: 3000,
    },
    autoHeight: true,
    centeredSlides: true,
    navigation: {
      prevEl: ".slidePrev-btn",
      nextEl: ".slideNext-btn",
    },
    breakpoints: {
      640: {
        slidesPerView: 1,
      },
      992: {
        slidesPerView: 3,
      },
      1200: {
        slidesPerView: 3,
      },
    },
  });
})();
