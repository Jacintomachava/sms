// cart dropdown product delete
(function () {
  function CheckProductvalid() {
    let AllProducts = document.getElementsByClassName("cart-product");
    let HiddenProducts = document.getElementsByClassName("product-remove");
    if (AllProducts.length == HiddenProducts.length) {
      document.querySelector(".cart-empty").classList.add("show");
      document.querySelector(".cart-main-wrapper").classList.add("d-none");
    }
  }
  const cart_details = document.getElementsByClassName("cart-product");
  cart_details.forEach((item, index) => {
    let DeleteButton = item.querySelector(".close-circle");
    DeleteButton.addEventListener("click", (event) => {
      item.classList.add("product-remove");
      CheckProductvalid();
    });
  });
})();
