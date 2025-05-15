




// 01. Freeze Headers 

if (document.documentElement.clientWidth < 980) {
    $(window).scroll(function() {
        var sc = $(window).scrollTop()
        if (sc >250) {
            $(".top-page-header").addClass("freeze-header");
        } else {
            $(".top-page-header").removeClass("freeze-header");
        }
    });
}




// 02. Mobile restaurant-menu-filter 
$("#restaurant-menu-filter-btn").click(function() {
    $(this).toggleClass("hamburger-open");
});

$('body').click(function() {
    $('.restaurant-menu-filter').removeClass('restaurant-menu-filter-open');
    $("#restaurant-menu-filter-btn").removeClass("hamburger-open");
    $(".restaurant-menu-filter-overlay").removeClass("restaurant-menu-filter-overlay-active");
});
$('#restaurant-menu-filter-btn').click(function(event, handler) {

    event.stopPropagation();

    $('.restaurant-menu-filter').toggleClass('restaurant-menu-filter-open');

    $('.restaurant-menu-filter-overlay').toggleClass('restaurant-menu-filter-overlay-active');
});





//07. Carousel

$(document).ready(function() {
    $('#main-slider').owlCarousel({
        margin: 0,
        stagePadding: 0,
        nav: true,
        dots:true,
        autoplay: true,
        autoplayTimeout: 5000,
        loop: true,
        smartSpeed: 1050,
        items: 1,
        navText: ["<div class='nav-button owl-prev'><i class='fal fa-chevron-left'></i> </div>‹</div>", "<div class='nav-button owl-next'><i class='fal fa-chevron-right'></i></div>"],

    });
});



const nav = () => {
  const nav = document.querySelector(".js-nav");
  const navLinks = nav.querySelectorAll(".nav__link");
  const slideRect = nav.querySelector(".nav__slider-rect");

  nav.addEventListener("click", (evt) => {
     if (!evt.target.classList.contains("nav__link")) {
        return;
     }
     evt.preventDefault();

     navLinks.forEach((item) => {
        item.classList.remove("nav__link_active");
     });

     if (!evt.target.classList.contains("nav__link_active")) {
        evt.target.classList.add("nav__link_active");
     }

     slideRect.style.transform = `translateX(${evt.target.dataset.transform}%)`;
  });
};
nav();



$(function () {

  var activeIndex = $('.active-tab').index(),
      $contentlis = $('.tab-contents .tab-content-item'),
      $tabslis = $('.nav__list li');
  
  // Show content of active tab on loads
  $contentlis.eq(activeIndex).addClass('tab-is-open');

  $('.nav__list').on('click', 'li', function (e) {
    var $current = $(e.currentTarget),
        index = $current.index();
    
    $tabslis.removeClass('active-tab');
    $current.addClass('active-tab');
    $contentlis.removeClass('tab-is-open').eq(index).addClass('tab-is-open');
	 });
});







// Get all sections that have an ID defined
const sections = document.querySelectorAll("section[id]");

// Add an event listener listening for scroll
window.addEventListener("scroll", navHighlighter);

function navHighlighter() {
  
  // Get current scroll position
  let scrollY = window.pageYOffset;
  
  // Now we loop through sections to get height, top and ID values for each
  sections.forEach(current => {
    const sectionHeight = current.offsetHeight;
    const sectionTop = current.offsetTop + 100;
    sectionId = current.getAttribute("id");
    
    /*
    - If our current scroll position enters the space where current section on screen is, add .active class to corresponding navigation link, else remove it
    - To know which link needs an active class, we use sectionId variable we are getting while looping through sections as an selector
    */
    if (
      scrollY > sectionTop &&
      scrollY <= sectionTop + sectionHeight
    ){
      document.querySelector(".menu-categories a[href*=" + sectionId + "]").classList.add("active-category");
    } else {
      document.querySelector(".menu-categories a[href*=" + sectionId + "]").classList.remove("active-category");
    }
  });
}







$(function() {
    // This will select everything with the class smoothScroll
    // This should prevent problems with carousel, scrollspy, etc...
    $('a').click(function() {
      if (location.pathname.replace(/^\//, '') == this.pathname.replace(/^\//, '') && location.hostname == this.hostname) {
        var target = $(this.hash);
        target = target.length ? target : $('[name=' + this.hash.slice(1) + ']');
        if (target.length) {
          $('html,body').animate({
            scrollTop: target.offset().top
          }, 1000); // The number here represents the speed of the scroll in milliseconds
          return false;
        }
      }
    });
  });
  
  // Change the speed to whatever you want
  // Personally i think 1000 is too much
  // Try 800 or below, it seems not too much but it will make a difference