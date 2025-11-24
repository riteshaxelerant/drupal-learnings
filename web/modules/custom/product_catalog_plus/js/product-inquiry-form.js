/**
 * @file
 * JavaScript for Product Inquiry Form AJAX handling.
 */

(function ($, Drupal, once) {
  'use strict';

  Drupal.behaviors.productInquiryForm = {
    attach: function (context, settings) {
      // Handle AJAX form submission messages using modern once() function.
      once('product-inquiry-form', 'form[id*="product_catalog_plus_inquiry_form"]', context).forEach(function (form) {
        var $form = $(form);
        
        // Listen for AJAX completion events.
        $form.on('ajaxComplete', function (event, xhr, settings) {
          // Check if this is our form submission.
          if (settings.url && settings.url.indexOf('product_catalog_plus_inquiry_form') !== -1) {
            // Add a small delay to ensure messages are processed.
            setTimeout(function () {
              // Scroll to the form if there are messages.
              var $messages = $form.find('.messages');
              if ($messages.length > 0) {
                $('html, body').animate({
                  scrollTop: $form.offset().top - 100
                }, 500);
              }
            }, 100);
          }
        });

        // Handle form reset after successful submission.
        $form.on('submit', function () {
          // Store the submit button to re-enable it if needed.
          var $submitButton = $form.find('input[type="submit"]');
          $submitButton.prop('disabled', true);
          
          // Re-enable button after a delay.
          setTimeout(function () {
            $submitButton.prop('disabled', false);
          }, 2000);
        });
      });
    }
  };

})(jQuery, Drupal, once); 