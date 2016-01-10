/**
 * Some common scripts
 *
 * @author      Knut Kohl <github@knutkohl.de>
 * @copyright   2012-2013 Knut Kohl
 * @license     GNU General Public License http://www.gnu.org/licenses/gpl.txt
 * @version     1.0.0
 */

console.time('Duration');

/**
 * Idea from http://pastebin.com/jYqm9ZcQ
 */
$.fn.autoWidth = function(options) {
    var settings = {
        marginRight: 20 /* Extra margin right pixels */
    };
    if (options) $.extend(settings, options);

    var maxWidth = 0;
    this.each(function() {
        maxWidth = Math.max($(this).width(), maxWidth);
    });

    this.css('display', 'inline-block').width(maxWidth + settings.marginRight);
};

/**
 * Idea from http://paulgueller.com/2011/04/26/parse-the-querystring-with-jquery/
 */
$.parseQueryString = function() {
    var qs = window.location.search.replace('?', ''), result = {}, v;
    if (qs) {
        $.each(qs.split('&'), function(id, data) {
            v = data.split('=');
            if (v.length == 2) result[v[0]] = decodeURIComponent(v[1]);
        });
    }
    return result;
};

/**
 * Display wait cursor for whole page
 */
$.wait = function(show) {
    if (!arguments.length) show = true; // Defaults to true
    $('html, body').css('cursor', show ? 'progress' : 'default');
};

/**
 *
 */
var lastModule, hideMenuTimer;

/**
 *
 */
$(function() {

    $.datepicker.setDefaults($.datepicker.regional[$.datepicker.regional[language] ? language : '']);

    // Inititilize Pines Notify
    $.pnotify.defaults.styling = 'jqueryui';
    $.pnotify.defaults.delay = 5000;
    $.pnotify.defaults.history = false;
    $.pnotify.defaults.sticker = false;
    $.pnotify.defaults.stack.spacing1 = 5;
    $.pnotify.defaults.stack.spacing2 = 15;
    $.pnotify.defaults.labels.stick = pnotify_defaults_labels_stick;
    $.pnotify.defaults.labels.close = pnotify_defaults_labels_close;

    $(messages).each(function(id, msg) {
        if (msg.type == 'error') {
            msg.history = true;
            msg.hide = false;
        }
        $.pnotify(msg);
    });

    // Inititilize Tooltips
    $('.tip, .tipbtn').tipTip({
        attribute: 'tip',
        maxWidth: '400px',
        edgeOffset: 10
    });
    $('.tip-right, .tipbtn-right').tipTip({
        attribute: 'tip',
        defaultPosition: 'right',
        maxWidth: '400px',
        edgeOffset: 10
    });
    $('.tip-top, .tipbtn-top').tipTip({
        attribute: 'tip',
        defaultPosition: 'top',
        maxWidth: '400px',
        edgeOffset: 10
    });
    $('#tiptip_content').addClass('ui-state-default');

    $('button, a.button, input[type=submit], input[type=checkbox].button, input[type=radio].button').each(function(id, el) {
        var options = { icons: { primary: null, secondary: null } };
        el = $(el);
        if (el.data('primary')) {
            options.icons.primary = el.data('primary');
        }
        if (el.data('secondary')) {
            options.icons.secondary = el.data('secondary');
        }
        if (!el.data('text')) {
            options.text = false;
        }
        el.button(options);
        el.prop('title', '');
    });

    $('.toolbar').buttonset();

    $('input[type=text], input[type=number], input[type=password], select, textarea').addClass('ui-corner-all');

    $('.numbersOnly').keyup(function () {
            if (this.value != this.value.replace(/[^0-9\.]/g, '')) {
                 this.value = this.value.replace(/[^0-9\.]/g, '');
            }
    });

    $('input.iCheck').iCheck({
        checkboxClass: 'icheckbox_flat',
        radioClass: 'iradio_flat'
    });

    $('input.iCheckLine').each(function(){
        var self = $(this),
        label = self.next(),
        label_text = label.text();
        label.remove();
        self.iCheck({
            checkboxClass: 'icheckbox_line',
            radioClass: 'iradio_line',
            insert: '<div class="icheck_line-icon"></div>' + label_text
        });
    });

    $('input').iCheck('update');

    $('input[type=number]').prop('type', 'text').addClass('number-spinner').spinner();

    // Back to top
    var fadeDuration = 500;
    $(window).on('scroll', function() {
        if ($(this).scrollTop() > 50) {
            $('.go-top').fadeIn(fadeDuration);
        } else {
            $('.go-top').fadeOut(fadeDuration);
        }
    });

    $('.go-top').on('click', function(e) {
        e.preventDefault();
        jQuery('html, body').animate({scrollTop: 0}, fadeDuration);
        return false;
    });

    $('.sm').smartmenus({ mainMenuSubOffsetY: 12 });

    $('.language').click(function(e) {
        e.preventDefault();
        /* Detect if there is already parameters in URL */
        var sep = (window.location.search == '') ? '?' : '&';
        window.location = window.location + sep + 'lang=' + $(this).data('lang');
        return false;
    });

    shortcut.add('Shift+F1', function() { window.location = '/'; });
    shortcut.add('Shift+F2', function() { window.location = '/dashboard'; });
    shortcut.add('Shift+F3', function() { window.location = '/channel'; });
    shortcut.add('Shift+F4', function() { window.location = '/overview'; });
    shortcut.add('Shift+F5', function() { window.location = '/list'; });
    shortcut.add('Shift+F6', function() { window.location = '/info'; });
    shortcut.add('Shift+F7', function() { window.location = '/description'; });
    shortcut.add('Shift+F8', function() { window.location = '/weather'; });
    shortcut.add('Alt+L',    function() { window.location = '/logout'; });
});

/**
 *
 */
var timer, verbose = false;

/**
 *
 */
function _log() {
    if (!verbose) return;

    console.timeEnd('Duration');
    console.time('Duration');
    if (arguments.length == 1) {
        console.log(arguments[0]);
    } else {
        $(arguments).each(function(id, data) {
            if (id == 0) {
                console.group(data);
            } else {
                if (Object.prototype.toString.call(data) === '[object Array]') {
                    console.table(data);
/*
                } else if (typeof data == 'object') {
                    console.dir(data);
*/
                } else {
                    console.log(data);
                }
            }
        });
        console.groupEnd();
    }
}

/**
 * disabled, not used yet
 * /
String.prototype.repeat = function(count) {
    if (count < 1) return '';
    var result = '', pattern = this.valueOf();
    while (count > 0) {
        if (count & 1) result += pattern;
        count >>= 1, pattern += pattern;
    }
    return result;
};
*/
