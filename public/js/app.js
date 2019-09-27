/*
 *  Document   : app.js
 *  Author     : pixelcave
 *  Description: Custom scripts and plugin initializations (available to all pages)
 *
 *  Feel free to remove the plugin initilizations from uiInit() if you would like to
 *  use them only in specific pages. Also, if you remove a js plugin you won't use, make
 *  sure to remove its initialization from uiInit().
 */

var App = function() {
    /* Helper variables - set in uiInit() */
    var page, pageContent, header, footer, sidebar, sScroll, sidebarAlt, sScrollAlt;

    /* Initialization UI Code */
    var uiInit = function() {
        // Set variables - Cache some often used Jquery objects in variables */
        page            = $('#page-container');
        pageContent     = $('#page-content');
        header          = $('header');
        footer          = $('#page-content + footer');

        sidebar         = $('#sidebar');
        sScroll         = $('#sidebar-scroll');

        sidebarAlt      = $('#sidebar-alt');
        sScrollAlt      = $('#sidebar-alt-scroll');

        // Initialize sidebars functionality
        handleSidebar('init');

        // Sidebar navigation functionality
        handleNav();

        // Interactive blocks functionality
        interactiveBlocks();

        // Scroll to top functionality
        scrollToTop();

        // Template Options, change features
        templateOptions();

        // Resize #page-content to fill empty space if exists (also add it to resize and orientationchange events)
        resizePageContent();
        $(window).resize(function(){ resizePageContent(); });
        $(window).bind('orientationchange', resizePageContent);

        // Add the correct copyright year at the footer
        var yearCopy = $('#year-copy'), d = new Date();
        if (d.getFullYear() === 2014) { yearCopy.html('2014'); } else { yearCopy.html('2014-' + d.getFullYear().toString().substr(2,2)); }

        // Initialize chat demo functionality (in sidebar)
        chatUi();

        // Initialize tabs
        $('[data-toggle="tabs"] a, .enable-tabs a').click(function(e){ e.preventDefault(); $(this).tab('show'); });

        // Initialize Tooltips
        $('[data-toggle="tooltip"], .enable-tooltip').tooltip({container: 'body', animation: false, html: true});

        // Initialize Popovers
        $('[data-toggle="popover"], .enable-popover').popover({container: 'body', animation: true});

        // Initialize single image lightbox
        $('[data-toggle="lightbox-image"]').magnificPopup({type: 'image', image: {titleSrc: 'title'}});

        // Initialize image gallery lightbox
        $('[data-toggle="lightbox-gallery"]').each(function(){
            $(this).magnificPopup({
                delegate: 'a.gallery-link',
                type: 'image',
                gallery: {
                    enabled: true,
                    navigateByImgClick: true,
                    arrowMarkup: '<button type="button" class="mfp-arrow mfp-arrow-%dir%" title="%title%"></button>',
                    tPrev: 'Previous',
                    tNext: 'Next',
                    tCounter: '<span class="mfp-counter">%curr% of %total%</span>'
                },
                image: {titleSrc: 'title'}
            });
        });

        // Initialize Typeahead - Example with countries
        var exampleTypeheadData = ["Afghanistan","Albania","Algeria","American Samoa","Andorra","Angola","Anguilla","Antarctica","Antigua and Barbuda","Argentina","Armenia","Aruba","Australia","Austria","Azerbaijan","Bahrain","Bangladesh","Barbados","Belarus","Belgium","Belize","Benin","Bermuda","Bhutan","Bolivia","Bosnia and Herzegovina","Botswana","Bouvet Island","Brazil","British Indian Ocean Territory","British Virgin Islands","Brunei","Bulgaria","Burkina Faso","Burundi","CΓ΄te d'Ivoire","Cambodia","Cameroon","Canada","Cape Verde","Cayman Islands","Central African Republic","Chad","Chile","China","Christmas Island","Cocos (Keeling) Islands","Colombia","Comoros","Congo","Cook Islands","Costa Rica","Croatia","Cuba","Cyprus","Czech Republic","Democratic Republic of the Congo","Denmark","Djibouti","Dominica","Dominican Republic","East Timor","Ecuador","Egypt","El Salvador","Equatorial Guinea","Eritrea","Estonia","Ethiopia","Faeroe Islands","Falkland Islands","Fiji","Finland","Former Yugoslav Republic of Macedonia","France","French Guiana","French Polynesia","French Southern Territories","Gabon","Georgia","Germany","Ghana","Gibraltar","Greece","Greenland","Grenada","Guadeloupe","Guam","Guatemala","Guinea","Guinea-Bissau","Guyana","Haiti","Heard Island and McDonald Islands","Honduras","Hong Kong","Hungary","Iceland","India","Indonesia","Iran","Iraq","Ireland","Israel","Italy","Jamaica","Japan","Jordan","Kazakhstan","Kenya","Kiribati","Kuwait","Kyrgyzstan","Laos","Latvia","Lebanon","Lesotho","Liberia","Libya","Liechtenstein","Lithuania","Luxembourg","Macau","Madagascar","Malawi","Malaysia","Maldives","Mali","Malta","Marshall Islands","Martinique","Mauritania","Mauritius","Mayotte","Mexico","Micronesia","Moldova","Monaco","Mongolia","Montserrat","Morocco","Mozambique","Myanmar","Namibia","Nauru","Nepal","Netherlands","Netherlands Antilles","New Caledonia","New Zealand","Nicaragua","Niger","Nigeria","Niue","Norfolk Island","North Korea","Northern Marianas","Norway","Oman","Pakistan","Palau","Panama","Papua New Guinea","Paraguay","Peru","Philippines","Pitcairn Islands","Poland","Portugal","Puerto Rico","Qatar","RΓ©union","Romania","Russia","Rwanda","SΓ£o TomΓ© and PrΓ­ncipe","Saint Helena","Saint Kitts and Nevis","Saint Lucia","Saint Pierre and Miquelon","Saint Vincent and the Grenadines","Samoa","San Marino","Saudi Arabia","Senegal","Seychelles","Sierra Leone","Singapore","Slovakia","Slovenia","Solomon Islands","Somalia","South Africa","South Georgia and the South Sandwich Islands","South Korea","Spain","Sri Lanka","Sudan","Suriname","Svalbard and Jan Mayen","Swaziland","Sweden","Switzerland","Syria","Taiwan","Tajikistan","Tanzania","Thailand","The Bahamas","The Gambia","Togo","Tokelau","Tonga","Trinidad and Tobago","Tunisia","Turkey","Turkmenistan","Turks and Caicos Islands","Tuvalu","US Virgin Islands","Uganda","Ukraine","United Arab Emirates","United Kingdom","United States","United States Minor Outlying Islands","Uruguay","Uzbekistan","Vanuatu","Vatican City","Venezuela","Vietnam","Wallis and Futuna","Western Sahara","Yemen","Yugoslavia","Zambia","Zimbabwe"];
        $('.input-typeahead').typeahead({ source: exampleTypeheadData });

        // Initialize Chosen
        $('.select-chosen').chosen({width: "100%"}).chosenReadonly();

		$('.select-chosen2').chosen({width:"auto"});

        // Initialize Select2
        $('.select-select2').select2();

        // Initialize Bootstrap Colorpicker
        $('.input-colorpicker').colorpicker({format: 'hex'});
        $('.input-colorpicker-rgba').colorpicker({format: 'rgba'});

        // Initialize Slider for Bootstrap
        $('.input-slider').slider();

        // Initialize Tags Input
        $('.input-tags').tagsInput({ width: 'auto', height: 'auto'});

        // Initialize Datepicker
        $('.input-datepicker, .input-daterange').datepicker({weekStart: 1, language: "fr", todayHighlight: true});
        $('.input-datepicker-close').datepicker({weekStart: 1}).on('changeDate', function(e){ $(this).datepicker('hide'); });

        // Initialize Timepicker
        $('.input-timepicker').timepicker({minuteStep: 1,showSeconds: true,showMeridian: true});
        $('.input-timepicker24').timepicker({minuteStep: 5,showSeconds: false,showMeridian: false});

        // Easy Pie Chart
        $('.pie-chart').easyPieChart({
            barColor: $(this).data('bar-color') ? $(this).data('bar-color') : '#777777',
            trackColor: $(this).data('track-color') ? $(this).data('track-color') : '#eeeeee',
            lineWidth: $(this).data('line-width') ? $(this).data('line-width') : 3,
            size: $(this).data('size') ? $(this).data('size') : '80',
            animate: 800,
            scaleColor: false
        });

        // Initialize Placeholder
        $('input, textarea').placeholder();
    };

    /* Page Loading functionality */
    var pageLoading = function(){
        var pageWrapper = $('#page-wrapper');

        if (pageWrapper.hasClass('page-loading')) {
            pageWrapper.removeClass('page-loading');
        }
    };

    /* Gets window width cross browser */
    var getWindowWidth = function(){
        return window.innerWidth
                || document.documentElement.clientWidth
                || document.body.clientWidth;
    };

    /* Sidebar Navigation functionality */
    var handleNav = function() {
        // Animation Speed, change the values for different results
        var upSpeed     = 250;
        var downSpeed   = 250;

        // Get all vital links
        var menuLinks       = $('.sidebar-nav-menu');
        var submenuLinks    = $('.sidebar-nav-submenu');

        // Primary Accordion functionality
        menuLinks.click(function(){
            var link = $(this);

            if (page.hasClass('sidebar-mini') && page.hasClass('sidebar-visible-lg-mini') && (getWindowWidth() > 991)) {
                if (link.hasClass('open')) {
                    link.removeClass('open');
                }
                else {
                    $('.sidebar-nav-menu.open').removeClass('open');
                    link.addClass('open');
                }
            }
            else if (!link.parent().hasClass('active')) {
                if (link.hasClass('open')) {
                    link.removeClass('open').next().slideUp(upSpeed, function(){
                        handlePageScroll(link, 200, 300);
                    });

                    // Resize #page-content to fill empty space if exists
                    setTimeout(resizePageContent, upSpeed);
                }
                else {
                    $('.sidebar-nav-menu.open').removeClass('open').next().slideUp(upSpeed);
                    link.addClass('open').next().slideDown(downSpeed, function(){
                        handlePageScroll(link, 150, 600);
                    });

                    // Resize #page-content to fill empty space if exists
                    setTimeout(resizePageContent, ((upSpeed > downSpeed) ? upSpeed : downSpeed));
                }
            }

            link.blur();

            return false;
        });

        // Submenu Accordion functionality
        submenuLinks.click(function(){
            var link = $(this);

            if (link.parent().hasClass('active') !== true) {
                if (link.hasClass('open')) {
                    link.removeClass('open').next().slideUp(upSpeed, function(){
                        handlePageScroll(link, 200, 300);
                    });

                    // Resize #page-content to fill empty space if exists
                    setTimeout(resizePageContent, upSpeed);
                }
                else {
                    link.closest('ul').find('.sidebar-nav-submenu.open').removeClass('open').next().slideUp(upSpeed);
                    link.addClass('open').next().slideDown(downSpeed, function(){
                        handlePageScroll(link, 150, 600);
                    });

                    // Resize #page-content to fill empty space if exists
                    setTimeout(resizePageContent, ((upSpeed > downSpeed) ? upSpeed : downSpeed));
                }
            }

            link.blur();

            return false;
        });
    };

    /* Scrolls the page (static layout) or the sidebar scroll element (fixed header/sidebars layout) to a specific position - Used when a submenu opens */
    var handlePageScroll = function(sElem, sHeightDiff, sSpeed) {
        if (!page.hasClass('disable-menu-autoscroll')) {
            var elemScrollToHeight;

            // If we have a static layout scroll the page
            if (!header.hasClass('navbar-fixed-top') && !header.hasClass('navbar-fixed-bottom')) {
                var elemOffsetTop   = sElem.offset().top;

                elemScrollToHeight  = (((elemOffsetTop - sHeightDiff) > 0) ? (elemOffsetTop - sHeightDiff) : 0);

                $('html, body').animate({scrollTop: elemScrollToHeight}, sSpeed);
            } else { // If we have a fixed header/sidebars layout scroll the sidebar scroll element
                var sContainer      = sElem.parents('#sidebar-scroll');
                var elemOffsetCon   = sElem.offset().top + Math.abs($('div:first', sContainer).offset().top);

                elemScrollToHeight = (((elemOffsetCon - sHeightDiff) > 0) ? (elemOffsetCon - sHeightDiff) : 0);
                sContainer.animate({ scrollTop: elemScrollToHeight}, sSpeed);
            }
        }
    };

    /* Sidebar Functionality */
    var handleSidebar = function(mode, extra) {
        if (mode === 'init') {
            // Init sidebars scrolling functionality
            handleSidebar('sidebar-scroll');
            handleSidebar('sidebar-alt-scroll');

            // Close the other sidebar if we hover over a partial one
            // In smaller screens (the same applies to resized browsers) two visible sidebars
            // could mess up our main content (not enough space), so we hide the other one :-)
            $('.sidebar-partial #sidebar')
                .mouseenter(function(){ handleSidebar('close-sidebar-alt'); });
            $('.sidebar-alt-partial #sidebar-alt')
                .mouseenter(function(){ handleSidebar('close-sidebar'); });
        } else {
            var windowW = getWindowWidth();

            if (mode === 'toggle-sidebar') {
                if ( windowW > 991) { // Toggle main sidebar in large screens (> 991px)
                    page.toggleClass('sidebar-visible-lg');

                    if (page.hasClass('sidebar-mini')) {
                        page.toggleClass('sidebar-visible-lg-mini');
                    }

                    if (page.hasClass('sidebar-visible-lg')) {
                        handleSidebar('close-sidebar-alt');
                    }

                    // If 'toggle-other' is set, open the alternative sidebar when we close this one
                    if (extra === 'toggle-other') {
                        if (!page.hasClass('sidebar-visible-lg')) {
                            handleSidebar('open-sidebar-alt');
                        }
                    }
                } else { // Toggle main sidebar in small screens (< 992px)
                    page.toggleClass('sidebar-visible-xs');

                    if (page.hasClass('sidebar-visible-xs')) {
                        handleSidebar('close-sidebar-alt');
                    }
                }

                // Handle main sidebar scrolling functionality
                handleSidebar('sidebar-scroll');
            }
            else if (mode === 'toggle-sidebar-alt') {
                if ( windowW > 991) { // Toggle alternative sidebar in large screens (> 991px)
                    page.toggleClass('sidebar-alt-visible-lg');

                    if (page.hasClass('sidebar-alt-visible-lg')) {
                        handleSidebar('close-sidebar');
                    }

                    // If 'toggle-other' is set open the main sidebar when we close the alternative
                    if (extra === 'toggle-other') {
                        if (!page.hasClass('sidebar-alt-visible-lg')) {
                            handleSidebar('open-sidebar');
                        }
                    }
                } else { // Toggle alternative sidebar in small screens (< 992px)
                    page.toggleClass('sidebar-alt-visible-xs');

                    if (page.hasClass('sidebar-alt-visible-xs')) {
                        handleSidebar('close-sidebar');
                    }
                }
            }
            else if (mode === 'open-sidebar') {
                if ( windowW > 991) { // Open main sidebar in large screens (> 991px)
                    if (page.hasClass('sidebar-mini')) { page.removeClass('sidebar-visible-lg-mini'); }
                    page.addClass('sidebar-visible-lg');
                } else { // Open main sidebar in small screens (< 992px)
                    page.addClass('sidebar-visible-xs');
                }

                // Close the other sidebar
                handleSidebar('close-sidebar-alt');
            }
            else if (mode === 'open-sidebar-alt') {
                if ( windowW > 991) { // Open alternative sidebar in large screens (> 991px)
                    page.addClass('sidebar-alt-visible-lg');
                } else { // Open alternative sidebar in small screens (< 992px)
                    page.addClass('sidebar-alt-visible-xs');
                }

                // Close the other sidebar
                handleSidebar('close-sidebar');
            }
            else if (mode === 'close-sidebar') {
                if ( windowW > 991) { // Close main sidebar in large screens (> 991px)
                    page.removeClass('sidebar-visible-lg');
                    if (page.hasClass('sidebar-mini')) { page.addClass('sidebar-visible-lg-mini'); }
                } else { // Close main sidebar in small screens (< 992px)
                    page.removeClass('sidebar-visible-xs');
                }
            }
            else if (mode === 'close-sidebar-alt') {
                if ( windowW > 991) { // Close alternative sidebar in large screens (> 991px)
                    page.removeClass('sidebar-alt-visible-lg');
                } else { // Close alternative sidebar in small screens (< 992px)
                    page.removeClass('sidebar-alt-visible-xs');
                }
            }
            else if (mode === 'sidebar-scroll') { // Handle main sidebar scrolling
                if (page.hasClass('sidebar-mini') && page.hasClass('sidebar-visible-lg-mini') && (windowW > 991)) { // Destroy main sidebar scrolling when in mini sidebar mode
                    if (sScroll.length && sScroll.parent('.slimScrollDiv').length) {
                        sScroll
                            .slimScroll({destroy: true});
                        sScroll
                            .attr('style', '');
                    }
                }
                else if ((page.hasClass('header-fixed-top') || page.hasClass('header-fixed-bottom'))) {
                    var sHeight = $(window).height();

                    if (sScroll.length && (!sScroll.parent('.slimScrollDiv').length)) { // If scrolling does not exist init it..
                        sScroll
                            .slimScroll({
                                height: sHeight,
                                color: '#fff',
                                size: '3px',
                                touchScrollStep: 100
                            });

                        // Handle main sidebar's scrolling functionality on resize or orientation change
                        var sScrollTimeout;

                        $(window).on('resize orientationchange', function(){
                            clearTimeout(sScrollTimeout);

                            sScrollTimeout = setTimeout(function(){
                                handleSidebar('sidebar-scroll');
                            }, 150);
                        });
                    }
                    else { // ..else resize scrolling height
                        sScroll
                            .add(sScroll.parent())
                            .css('height', sHeight);
                    }
                }
            }
            else if (mode === 'sidebar-alt-scroll') { // Init alternative sidebar scrolling
                if ((page.hasClass('header-fixed-top') || page.hasClass('header-fixed-bottom'))) {
                    var sHeightAlt = $(window).height();

                    if (sScrollAlt.length && (!sScrollAlt.parent('.slimScrollDiv').length)) { // If scrolling does not exist init it..
                        sScrollAlt
                            .slimScroll({
                                height: sHeightAlt,
                                color: '#fff',
                                size: '3px',
                                touchScrollStep: 100
                            });

                        // Resize alternative sidebar scrolling height on window resize or orientation change
                        var sScrollAltTimeout;

                        $(window).on('resize orientationchange', function(){
                            clearTimeout(sScrollAltTimeout);

                            sScrollAltTimeout = setTimeout(function(){
                                handleSidebar('sidebar-alt-scroll');
                            }, 150);
                        });
                    }
                    else { // ..else resize scrolling height
                        sScrollAlt
                            .add(sScrollAlt.parent())
                            .css('height', sHeightAlt);
                    }
                }
            }
        }

        return false;
    };

    /* Resize #page-content to fill empty space if exists */
    var resizePageContent = function() {
        var windowH         = $(window).height();
        var sidebarH        = sidebar.outerHeight();
        var sidebarAltH     = sidebarAlt.outerHeight();
        var headerH         = header.outerHeight();
        var footerH         = footer.outerHeight();

        // If we have a fixed sidebar/header layout or each sidebars’ height < window height
        if (header.hasClass('navbar-fixed-top') || header.hasClass('navbar-fixed-bottom') || ((sidebarH < windowH) && (sidebarAltH < windowH))) {
            if (page.hasClass('footer-fixed')) { // if footer is fixed don't remove its height
                pageContent.css('min-height', windowH - headerH + 'px');
            } else { // else if footer is static, remove its height
                pageContent.css('min-height', windowH - (headerH + footerH) + 'px');
            }
        }  else { // In any other case set #page-content height the same as biggest sidebar's height
            if (page.hasClass('footer-fixed')) { // if footer is fixed don't remove its height
                pageContent.css('min-height', ((sidebarH > sidebarAltH) ? sidebarH : sidebarAltH) - headerH + 'px');
            } else { // else if footer is static, remove its height
                pageContent.css('min-height', ((sidebarH > sidebarAltH) ? sidebarH : sidebarAltH) - (headerH + footerH) + 'px');
            }
        }
    };

    /* Interactive blocks functionality */
    var interactiveBlocks = function() {

        // Toggle block's content
        $('[data-toggle="block-toggle-content"]').on('click', function(){
            var blockContent = $(this).closest('.block').find('.block-content');

            if ($(this).hasClass('active')) {
                blockContent.slideDown();
            } else {
                blockContent.slideUp();
            }

            $(this).toggleClass('active');
        });

        // Toggle block fullscreen
        $('[data-toggle="block-toggle-fullscreen"]').on('click', function(){
            var block = $(this).closest('.block');

            if ($(this).hasClass('active')) {
                block.removeClass('block-fullscreen');
            } else {
                block.addClass('block-fullscreen');
            }

            $(this).toggleClass('active');
        });

        // Hide block
        $('[data-toggle="block-hide"]').on('click', function(){
            $(this).closest('.block').fadeOut();
        });
    };

    /* Scroll to top functionality */
    var scrollToTop = function() {
        // Get link
        var link = $('#to-top');

        $(window).scroll(function() {
            // If the user scrolled a bit (150 pixels) show the link in large resolutions
            if (($(this).scrollTop() > 150) && (getWindowWidth() > 991)) {
                link.fadeIn(100);
            } else {
                link.fadeOut(100);
            }
        });

        // On click get to top
        link.click(function() {
            $('html, body').animate({scrollTop: 0}, 400);
            return false;
        });
    };

    /* Demo chat functionality (in sidebar) */
    var chatUi = function() {
        var chatUsers       = $('.chat-users');
        var chatTalk        = $('.chat-talk');
        var chatMessages    = $('.chat-talk-messages');
        var chatInput       = $('#sidebar-chat-message');
        var chatMsg         = '';

        // Initialize scrolling on chat talk list
        $('.chat-talk-messages').slimScroll({ height: 210, color: '#fff', size: '3px', position: 'left', touchScrollStep: 100 });

        // If a chat user is clicked show the chat talk
        $('a', chatUsers).click(function(){
            chatUsers.slideUp();
            chatTalk.slideDown();
            chatInput.focus();

            return false;
        });

        // If chat talk close button is clicked show the chat user list
        $('#chat-talk-close-btn').click(function(){
            chatTalk.slideUp();
            chatUsers.slideDown();

            return false;
        });

        // When the chat message form is submitted
        $('#sidebar-chat-form').submit(function(e){
            // Get text from message input
            chatMsg = chatInput.val();

            // If the user typed a message
            if (chatMsg) {
                // Add it to the message list
                chatMessages.append('<li class="chat-talk-msg chat-talk-msg-highlight themed-border animation-slideLeft">' + $('<div />').text(chatMsg).html() + '</li>');

                // Scroll the message list to the bottom
                chatMessages.animate({ scrollTop: chatMessages[0].scrollHeight}, 500);

                // Reset the message input
                chatInput.val('');
            }

            // Don't submit the message form
            e.preventDefault();
        });
    };

    /* Template Options, change features functionality */
    var templateOptions = function() {
        /*
         * Color Themes
         */
        var colorList   = $('.sidebar-themes');
        var themeLink   = $('#theme-link');

        var themeColor  = themeLink.length ? themeLink.attr('href') : 'default';
        var cookies     = page.hasClass('enable-cookies') ? true : false;

        var themeColorCke;

        // If cookies have been enabled
        if (cookies) {
            themeColorCke = Cookies.get('optionThemeColor') ? Cookies.get('optionThemeColor') : false;

            // Update color theme
            if (themeColorCke) {
                if (themeColorCke === 'default') {
                    if (themeLink.length) {
                        themeLink.remove();
                        themeLink = $('#theme-link');
                    }
                } else {
                    if (themeLink.length) {
                        themeLink.attr('href', themeColorCke);
                    } else {
                        $('link[href="css/themes.css"]')
                            .before('<link id="theme-link" rel="stylesheet" href="' + themeColorCke + '">');

                        themeLink = $('#theme-link');
                    }
                }
            }

            themeColor = themeColorCke ? themeColorCke : themeColor;
        }

        // Set the active color theme link as active
        $('a[data-theme="' + themeColor + '"]', colorList)
            .parent('li')
            .addClass('active');

        // When a color theme link is clicked
        $('a', colorList).click(function(e){
            // Get theme name
            themeColor = $(this).data('theme');

            $('li', colorList).removeClass('active');
            $(this).parent('li').addClass('active');

            if (themeColor === 'default') {
                if (themeLink.length) {
                    themeLink.remove();
                    themeLink = $('#theme-link');
                }
            } else {
                if (themeLink.length) {
                    themeLink.attr('href', themeColor);
                } else {
                    $('link[href="css/themes.css"]').before('<link id="theme-link" rel="stylesheet" href="' + themeColor + '">');
                    themeLink = $('#theme-link');
                }
            }

            // If cookies have been enabled, save the new options
            if (cookies) {
                Cookies.set('optionThemeColor', themeColor, {expires: 7});
            }
        });

        // Prevent template options dropdown from closing on clicking options
        $('.dropdown-options a').click(function(e){ e.stopPropagation(); });

        /* Page Style */
        var optMainStyle        = $('#options-main-style');
        var optMainStyleAlt     = $('#options-main-style-alt');

        if (page.hasClass('style-alt')) {
            optMainStyleAlt.addClass('active');
        } else {
            optMainStyle.addClass('active');
        }

        optMainStyle.click(function() {
            page.removeClass('style-alt');
            $(this).addClass('active');
            optMainStyleAlt.removeClass('active');
        });

        optMainStyleAlt.click(function() {
            page.addClass('style-alt');
            $(this).addClass('active');
            optMainStyle.removeClass('active');
        });

        /* Header options */
        var optHeaderDefault    = $('#options-header-default');
        var optHeaderInverse    = $('#options-header-inverse');

        if (header.hasClass('navbar-default')) {
            optHeaderDefault.addClass('active');
        } else {
            optHeaderInverse.addClass('active');
        }

        optHeaderDefault.click(function() {
            header.removeClass('navbar-inverse').addClass('navbar-default');
            $(this).addClass('active');
            optHeaderInverse.removeClass('active');
        });

        optHeaderInverse.click(function() {
            header.removeClass('navbar-default').addClass('navbar-inverse');
            $(this).addClass('active');
            optHeaderDefault.removeClass('active');
        });
    };

    /* Datatables basic Bootstrap integration (pagination integration included under the Datatables plugin in plugins.js) */
    var dtIntegration = function() {
        $.extend(true, $.fn.dataTable.defaults, {
            "sDom": "<'row'<'col-sm-6 col-xs-5'l><'col-sm-6 col-xs-7'f>r>t<'row'<'col-sm-5 hidden-xs'i><'col-sm-7 col-xs-12 clearfix'p>>",
            "sPaginationType": "bootstrap",
            "oLanguage": {
                "sLengthMenu": "_MENU_",
                "sSearch": "<div class=\"input-group\">_INPUT_<span class=\"input-group-addon\"><i class=\"fa fa-search\"></i></span></div>",
                "sInfo": "<strong>_START_</strong>-<strong>_END_</strong> of <strong>_TOTAL_</strong>",
                "oPaginate": {
                    "sPrevious": "",
                    "sNext": ""
                }
            }
        });
        $.extend($.fn.dataTableExt.oStdClasses, {
            "sWrapper": "dataTables_wrapper form-inline",
            "sFilterInput": "form-control",
            "sLengthSelect": "form-control"
        });
    };

    /* Print functionality - Hides all sidebars, prints the page and then restores them (To fix an issue with CSS print styles in webkit browsers)  */
    var handlePrint = function() {
        // Store all #page-container classes
        var pageCls = page.prop('class');

        // Remove all classes from #page-container
        page.prop('class', '');

        // Print the page
        window.print();

        // Restore all #page-container classes
        page.prop('class', pageCls);
    };

    return {
        init: function() {
            uiInit(); // Initialize UI Code
            pageLoading(); // Initialize Page Loading
        },
        sidebar: function(mode, extra) {
            handleSidebar(mode, extra); // Handle sidebars - access functionality from everywhere
        },
        datatables: function() {
            dtIntegration(); // Datatables Bootstrap integration
        },
        pagePrint: function() {
            handlePrint(); // Print functionality
        }
    };
}();

/* Initialize app when page loads */
$(function(){ 
	App.init(); 

	$('.btmsgread').click(function() {
		var obj = $(this);
		$.post('crmajax.php', {action:'read-message', idmsg: obj.attr('data-id')}, function(resp) {
			if (resp.code == 'SUCCESS') {
				obj.parents('.sidebar-section').slideUp(300, function() {
					obj.parents('.sidebar-section').remove();
					if ($('#sidebar-alt-scroll .sidebar-section').length == 0)
						$('.label-indicator.animation-floating').text('');
					else
						$('.label-indicator.animation-floating').text($('#sidebar-alt-scroll .sidebar-section').length);
				});
			}
		}, 'json');
		return false;
	});
	
	
	var cursearch = false;
	$('#top-search').keyup(function(e) {
		setTimeout(function() {
			if (!cursearch && $('#top-search').val() != '' && $('#top-search').val().length >= 3) {
				cursearch = true;
				$.post('crmajax.php', {action:'global-search', txt: $('#top-search').val()}, function(resp) {
					if (resp.code == 'SUCCESS') {
						//console.log(resp.datas);
						if (resp.datas.length > 0) {
							var str = '';
							for(var d in resp.datas) {
								var data = resp.datas[d];								
								str += '<li class="media" onclick="location.href=\''+(data.type == 0 ? 'contact.php?id_contact='+data.id : 'installator.php?id_installator='+data.id)+'\';">';
								str += '<a href="'+(data.type == 0 ? 'contact.php?id_contact='+data.id : 'installator.php?id_installator='+data.id)+'" class="pull-left"><i class="gi '+(data.type == 0 ? 'gi-parents' : 'gi-cars')+'"></i></a>';
								str += '<div class="media-body">';
								str += '<a href="'+(data.type == 0 ? 'contact.php?id_contact='+data.id : 'installator.php?id_installator='+data.id)+'"><h5><strong>'+(data.type == 0 ? 'CLIENT' : 'INSTALLATEUR')+' : '+data.name.toUpperCase()+'</strong></h5></a>';
								str += '<p><i class="fa fa-phone"></i> Tel : '+data.tel1+' '+data.tel2+' | <i class="fa fa-at"></i>  Email : '+data.email+'<br>';
								str += '<i class="fa fa-map-marker"></i> '+data.adr1+' '+data.post_code+' '+data.city+' '+(data.type == 0 ? '('+data.dept+') <br><span class="label label-warning">Confirmateur: '+data.conf_name+'</span>' : '')+'</p>';
								str += '</li>';
							}
							$('#searchres .media-list').html(str.replace(new RegExp($('#top-search').val(), 'gi'), '<strong><u>'+$('#top-search').val().toUpperCase()+'</u></strong>'));
							$('#searchres').show();
						}
						else
							$('#searchres').hide();
					}
					else
						$('#searchres').hide();
					
					cursearch = false;
				}, 'json');
			}
			else
			if ($('#top-search').val() == '')
				$('#searchres').hide();
		}, 1000);
	});
	
	$('#top-search').blur(function() {
		setTimeout(function() {
			$('#searchres').hide();
			$('#top-search').val('');
		}, 500);
    });
    
    var popRecall = false;
	function checkRecall() {
		if (popRecall)
			return;
		if ($('.modal-open .modal').length > 0)
			return;
		
		var curtm = new Date(); //.getTime() / 1000;
		$.post('crmajax.php', {action:'check-recall', tm:curtm}, function(resp) {
			if (resp.code == 'SUCCESS') {
				//console.log(resp);
				var D = new Date(Date.parse(resp.data.date_recall.replace('-','/','g')));
				$('#recallinfodt').html('<small>Rappel à '+D.toString('dd MMM - HH:mm')+'</small>');
				$('#recallinfocli1').html('<b>'+resp.data.raison_sociale+'</b> - '+resp.data.first_name+' '+resp.data.last_name);
				$('#recallinfocli2').html('<label class="label label-info">'+resp.data.post_code+' '+resp.data.city+'</label>');
				$('#recallinfocli3').html('<label class="label label-success">'+resp.data.tel1+'</label>');
				$('#recallinfocli4').html(+resp.data.email);
				$('#modal-display-recall').attr('data-id', resp.data.id_comment);
				$('#modal-display-recall').attr('data-cli', resp.data.id_contact);
				$('#modal-display-recall').modal();
				popRecall = true;
			}
		}, 'json');
		
		setTimeout(function() { checkRecall(); }, 30000);
	}
	
	checkRecall();
	
	$('#lstremind a').click(function() {
		HoldOn.open();
		var curtm = new Date();
		$.post('crmajax.php', {action:'wait-recall', idcom:$('#modal-display-recall').attr('data-id'), idcli:$('#modal-display-recall').attr('data-cli'), tp:$(this).attr('data-id'), tm:curtm}, function(resp) {
			HoldOn.close();
			if (resp.code == 'SUCCESS') {
				$('#modal-display-recall').modal('hide');
				$('.btn-group.open').removeClass('open');
				popRecall = false;
			}
			else
				alert(resp.message);
		}, 'json');
		
		return false;
	});
	
	
	$('#lstvalidrec a').click(function() {
		HoldOn.open();
		var curtp = $(this).attr('data-id');
		$.post('crmajax.php', {action:'read-recall', idcom:$('#modal-display-recall').attr('data-id'), idcli:$('#modal-display-recall').attr('data-cli'), tp:curtp}, function(resp) {
			HoldOn.close();
			if (resp.code == 'SUCCESS') {
				if (curtp == '1')
					location.href = 'admin.php?page=crmcrm-contact&id_contact='+$('#modal-display-recall').attr('data-cli');
				$('#modal-display-recall').modal('hide');
				$('.btn-group.open').removeClass('open');
				popRecall = false;
			}
			else
				alert(resp.message);
		}, 'json');
		
		return false;
	});
});

function showCrmUser(idcrmuser) {
	if (idcrmuser == 0) {
		$('#id_crmuser').val('0');
        $('#row_team').show();
        $('#row_depts').show();
		$('#row_dts').hide();
        $('#frmcrmuser input, #frmcrmuser select').val('');
        $('#depts').chosen().val('');
		$('#id_profil, #id_team, #depts').trigger("chosen:updated");
		$('#modal-crmuser').modal();
	}
	else {			
		HoldOn.open();
		$.post('crmajax.php', {action:'get-crmuser', idcrmuser:idcrmuser}, function(resp) {
			HoldOn.close();
			if (resp.code == 'SUCCESS') {
				//$('#refresh_list_crmusers').click();
				//console.log(resp);
				for(var prop in resp.crmuser) {
					if ($('#'+prop).is('input') || $('#'+prop).is('select'))
						$('#'+prop).val(resp.crmuser[prop].replace(new RegExp("\\\\", "g"), ""));
					else
                        $('#'+prop).text(resp.crmuser[prop]);
                        
                    if (prop == 'depts')
                        $('#'+prop).val(resp.crmuser[prop].split(','));
                    if (prop == 'teams' && resp.crmuser[prop] != '') {
                        $('#id_team').attr('multiple', '');
                        $('#id_team').val(resp.crmuser[prop].split(','));                        
                    }
                }
                
				if (resp.crmuser.id_profil == '1' || resp.crmuser.id_profil == '5')
					$('#row_team').hide();
				else {
                    $('#row_team').show();					
                    if (resp.crmuser.id_profil == '3')
                        $('#id_team').chosen('destroy').attr('multiple', '').chosen({width:'100%'});
                    else
                        $('#id_team').chosen('destroy').removeAttr('multiple').chosen({width:'100%'});
                }
                    
                if (resp.crmuser.id_profil == '4' || resp.crmuser.id_profil == '5')
					$('#row_depts').show();
				else
                    $('#row_depts').hide();	
                    
				$('#id_profil, #id_team, #depts').trigger("chosen:updated");
				$('#row_dts').show();
				$('#modal-crmuser').modal();
			}
			else
				alert(resp.message);
			
			
		}, 'json');
	}
	return false;
}

$('#id_profil').change(function() {
	if ($(this).val() == '1' || $(this).val() == '5')
		$('#row_team').hide();
	else {
        $('#row_team').show();
        if ($(this).val() == '3')
            $('#id_team').chosen('destroy').attr('multiple', '').chosen({width:'100%'});
        else
            $('#id_team').chosen('destroy').removeAttr('multiple').chosen({width:'100%'});
    }
        
    if ($(this).val() == '4' || $(this).val() == '5')
		$('#row_depts').show();
	else
		$('#row_depts').hide();
});

$('#frmcrmuser').submit(function() {
	HoldOn.open();
	$.post('crmajax.php', {
        action:'update-crmuser', 
        data:$('#frmcrmuser').serialize(), 
        depts:$('#depts').chosen().val() != null ? $('#depts').chosen().val().join(',') : '', 
        teams:Array.isArray($('#id_team').chosen().val()) ? $('#id_team').chosen().val().join(',') : ''
    }, 
    function(resp) {
		HoldOn.close();
		if (resp.code == 'SUCCESS') {
			if ($('#id_crmuser').val() == '0' || $('#id_crmuser').val() == '')
				alert('Utilisateur ajouté !');
			else
				alert('Modification effectuée !')
			
            $('#list_crmusers').trigger("reloadGrid",[{current:true}]);
			$('#modal-crmuser').modal('hide');
		}
		else
			alert(resp.message);
	}, 'json');		
	
	return false;
});

function isogridcomplete(ids) {
	//console.log(ids);
	$("body").tooltip({ selector: '[data-toggle="tooltip"]', html:true });
}

function gridstatacty_onload(ids)
{
	if(ids.rows) 
		jQuery.each(ids.rows,function(i) {
			//console.log(this);
			var k = 0;
			var isrowsum = false;
			var isrowsum2 = false;
			for(var d in this) {
				//if (k > 0)
					if (this[d].substr(0, 1) == ' ' && k == 0) {
						isrowsum2 = true;
						break;
					}
					else
					if (this[d].substr(0, 1) == ' ') {
						isrowsum = true;
						break;
					}
				k++;
			}
			
			if (isrowsum2)
				jQuery('#list_activity_stats tr.jqgrow:eq('+i+')').css('background-image','inherit').css({'background-color':'#ffcaac'});
			else
			if (isrowsum)
				jQuery('#list_activity_stats tr.jqgrow:eq('+i+')').css('background-image','inherit').css({'background-color':'#f3edc2'});
		});
}

function gridstcontconf_onload(ids)
{
    if(ids.rows) 
    jQuery.each(ids.rows,function(i) {
        if (this.status_color != '' && this.status_color != undefined)	
            jQuery('#list_statuscontconf tr.jqgrow:eq('+i+')').css('background-image','inherit').css({'background-color':this.status_color});
    });
}