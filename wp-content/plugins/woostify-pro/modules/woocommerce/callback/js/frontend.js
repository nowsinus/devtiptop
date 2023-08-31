/**
 * Call Back Product
 *
 * @package Woostify Pro
 */

 'use strict';
 
var callbackPopupOpen = function () {
    var container_form = document.querySelector('#woostify-callback-form-quick-view');
    container_form.classList.add('quick-view-open');    
}

var callbackPopupClose = function () {
    var container_form = document.querySelector('#woostify-callback-form-quick-view');
    container_form.classList.remove('quick-view-open');
}

function woostifyCallbackProduct() {
    var btn_popup =document.querySelectorAll('#btn-callback-form-popup'),
        inner_form =document.querySelector('#woostify-callback-form-quick-view .woostify-callback-form-inner');
    var popup =btn_popup.length > 0 && undefined !== btn_popup[0] ? true : false,
        form  =document.querySelectorAll('#woostify-callback-form');
        
 
    if (true == popup) {
        form =document.querySelectorAll('#woostify-callback-form-quick-view');
        btn_popup[0].addEventListener(
            'click',
            function (e) {
                e.preventDefault(); 
                callbackPopupOpen();
            }
        );
    }    
 
    if (! form.length) {
        return;
    }     
 
    for (var i = 0, j = form.length; i < j; i++) {
        if (form[i].classList.contains('grouped_form')) {
            continue;
        }

        form[i].addEventListener(
            'click',
            function (e) {                
                if (form) {
                    form[0].addEventListener(
                        'click',
                        function (e) {                           
                            if (this !== e.target) {
                                return;
                            }
                            callbackPopupClose();
                        }
                    );
                }
            }
        );       
 
        var callbackForm =form[i],
            callback_name =callbackForm.querySelector('[name="callback_name"]'),
            callback_email =callbackForm.querySelector('[name="callback_email"]'),
            callback_phone =callbackForm.querySelector('[name="callback_phone_number"]'),
            callback_product_id =callbackForm.querySelector('[name="callback_product_id"]'),
            callback_variation_id =callbackForm.querySelector('[name="callback_variation_id"]'),
            callback_captcha =callbackForm.querySelector('.g-recaptcha'),
            callback_name_hide =callbackForm.querySelector('.form-input').getAttribute('data-name-hide'),
            callback_show_phone =callbackForm.querySelector('.form-input').getAttribute('data-show-phone'),
            callback_show_agree =callbackForm.querySelector('.form-input').getAttribute('data-show-agree'),
            callback_agree =callbackForm.querySelector('[name="callback_agree_checkbox_input"]'),
            button =callbackForm.querySelector('.callback_product_button');

        if (! button) {
            return;
        }
        
        button.addEventListener(
            'click',
            function (e) {
                e.preventDefault();
                
                const data = new FormData();
                data.append('action', 'woostify_callback_ajax_create_post');
                data.append('security', woostify_callback.security);
                if ( null != callback_captcha && 'undefined' != callback_captcha ) {
                    data.append('g-recaptcha-response', grecaptcha.getResponse());
                }                
                if( callback_name_hide != 1){
                data.append('name', callback_name.value);
                }
                data.append('email', callback_email.value);
                if( callback_show_phone == 1){
                data.append('phone', callback_phone.value);
                }
                if(callback_show_agree == 1 && callback_agree.checked ){
                    data.append('agree', callback_agree.value);
                }
                data.append('product_id', callback_product_id.value);
                data.append('variation_id', callback_variation_id.value);

                this.style.opacity = '0.5';
                this.style.pointerEvents = 'none';
                fetch(
                    woostify_callback.ajax_url, {
                        method: "POST",
                        credentials: 'same-origin',
                        body: data
                    }
                ).then(
                    function (res) {
                        if (200 !== res.status) {
                            console.log('Status Code: ' + res.status);
                            throw res;
                        }

                        return res.json();
                    }
                ).then(
                    function (response) {
                        if (response.success) {                            
                            if (true == popup) {
                                setTimeout(callbackPopupClose, 3000);
                                setTimeout(
                                    function () {
                                        form[0].querySelector('.callback_product_output_error').innerHTML = '';
                                    }, 3000
                                );
                            }
                            if( callback_name_hide != 1){
                            callback_name.value = '';
                            }
                            callback_email.value = '';
                            if( callback_show_phone == 1){
                            callback_phone.value = '';
                            }
                            if( callback_show_agree == 1){
                            callback_agree.value = '';
                            }
                            button.style.opacity = '1';
                            button.style.pointerEvents = 'all';
                            form[0].querySelector('.callback_product_output_error').style.color = 'green';
                            form[0].querySelector('.callback_product_output_error').innerHTML = response.data;
                        }else{
                            button.style.opacity = '1';
                            button.style.pointerEvents = 'all';                            
                            form[0].querySelector('.callback_product_output_error').style.color = 'red';
                            form[0].querySelector('.callback_product_output_error').innerHTML = response.data;
                        }
                    }
                ).catch(
                    function (error) {
                        console.log(error);
                    }
                );
            }
        );
    }
}
 
document.addEventListener(
    'DOMContentLoaded',
    function () {
        woostifyCallbackProduct();
        var variations_form = jQuery( 'form.variations_form' ),
            body = jQuery( 'body' );
        variations_form.on('show_variation hide_variation found_variation', function(e) {
            e.preventDefault();
            woostifyCallbackProduct();
        });
    }
);