define([
    'jquery',
    'Magento_Ui/js/form/element/file-uploader'
], function ($, FileUploader) {
    'use strict';

    return FileUploader.extend({
        defaults: {
            previewTmpl: 'Boyoot_ArModel/ar-model-preview'
        },

        /**
         * Get preview of the file
         * @param {Object} file
         * @returns {String}
         */
        getFilePreview: function (file) {
            // Return the Apple AR icon placeholder
            return require.toUrl('Boyoot_ArModel/images/apple-ar-icon-placeholder.webp');
        },

        /**
         * Return true if file is image
         * @param {Object} file
         * @returns {Boolean}
         */
        isImage: function (file) {
            // Always return true to show our custom preview
            return true;
        }
    });
});
