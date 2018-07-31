/**
 * @file
 * Add JAIL for fancy loading of images.
 */

(function ($) {
    Drupal.behaviors.islandora_compound_object_JAIL = {
        attach: function (context, settings) {
            $('img.islandora-compound-object-jail').jail({
                triggerElement:'#block-islandoracompoundobjectjaildisplay',
                event: 'scroll',
                error: function ($img, options) {
                    if ($img.attr("src") == drupalSettings.islandora_compound_object.image_path) {
                        return;
                    }

                    $img.attr("data-src", drupalSettings.islandora_compound_object.image_path);
                    $img.trigger('scroll');
                }
            });
        }
    };
})(jQuery.noConflict(true));
