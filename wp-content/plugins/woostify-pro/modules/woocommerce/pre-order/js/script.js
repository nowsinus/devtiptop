/**
 * Countdown Pre Order
 *
 * @package Woostify Pro
 */

 'use strict';

// Sub Zero.
var sub_zero = function (n) {
    return ( n < 10 ) ? '0' + n : n;
}

// Countdown timer.
var woostifyCountdownPreorder = function () {
    var selector =document.querySelectorAll('.woostify-pre-order-product');    
    if (! selector.length) {
        return;
    }
    for (var i = 0, j = selector.length; i < j; i ++) {
        let element = selector[i],
            element_countdown =element.querySelector('.woostify-countdown-preorder');

        var wrap_countdown = element.querySelectorAll('.woostify-countdown-preorder-wrap');
        if (! wrap_countdown.length) {
            return;
        }

        var date_to =element_countdown.getAttribute('data-date-to');
        var pre_order_closed_label = element_countdown.getAttribute('data-closed-label');
        var pre_order_check_order = element_countdown.getAttribute('data-check-order');
        
        var date_end = new Date(date_to);
            date_end.setHours(23, 59, 0, 0);
        var countDownDate = new Date(date_end).getTime();
        
        var x =setInterval( 
            function () {
                var now = new Date().getTime();
                var distance = countDownDate - now;
                var days = Math.floor(distance / (1000 * 60 * 60 * 24));
                var hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                var minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                var seconds = Math.floor((distance % (1000 * 60)) / 1000);
                let daysHtml = element.querySelector('.woostify-preorder-timer-days'),
                    hoursHtml = element.querySelector('.woostify-preorder-timer-hours'),
                    minutesHtml = element.querySelector('.woostify-preorder-timer-minutes'),
                    secondsHtml = element.querySelector('.woostify-preorder-timer-seconds');
                
                if (countDownDate > now) {
                    daysHtml.innerHTML =sub_zero(days);
                    hoursHtml.innerHTML =sub_zero(hours);
                    minutesHtml.innerHTML =sub_zero(minutes);
                    secondsHtml.innerHTML =sub_zero(seconds);
                }
                
                if (distance < 0 || pre_order_check_order == 0) {
                    clearInterval(x);
                    element.querySelector('.woostify-countdown-preorder-wrap').innerHTML = '<div class="preorder-closed">'+pre_order_closed_label+'</div>';
                }
            }, 1000
        );
    }
}
document.addEventListener(
    'DOMContentLoaded',
    function () {
        woostifyCountdownPreorder();
    }
);