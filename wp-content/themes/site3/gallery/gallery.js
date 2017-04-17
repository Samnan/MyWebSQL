$(function(){
    $('#gallery li').each(function(idx) {
        $(this).data('index', (++idx));
    });

    $('#gallery').jcarousel({
        scroll: 5,
        initCallback: initCallbackFunction
    })
    
    function initCallbackFunction(carousel) {
        $('#img').bind('image-loaded',function() {
            var idx =  $('#gallery li.active').data('index') - 2;
            
            carousel.scroll(idx);
            return false;
        });
        
        // hotkeys plugin: use arrows to control the gallery
        $(document).bind('keydown', 'right', function (evt){ $.galleria.next(); });
        $(document).bind('keydown', 'left', function (evt){ $.galleria.prev(); });
        $(document).bind('keydown', 'up', function (evt){ $('.jcarousel-next-horizontal').click(); return false; });
        $(document).bind('keydown', 'down', function (evt){ $('.jcarousel-prev-horizontal').click(); return false; });
    };

    // load and fade-in thumbnails
    $('#gallery li img').css('opacity', 0).each(function() {    
        if (this.complete || this.readyState == 'complete') { $(this).animate({'opacity': 1}, 300) } 
        else { $(this).load(function() { $(this).animate({'opacity': 1}, 300) }); }
    });

    
    $('#gallery').galleria({
        // #img is the empty div which holds full size images
        insert: '#img',
        
        // enable history plugin
        history: true,
        
        // function fired when the image is displayed
        onImage: function(image, caption, thumb) {        
            // fade in the image 
            image.hide().fadeIn(500);
            
            // animate active thumbnail's opacity to 1, other list elements to 0.6
            thumb.parent().fadeTo(200, 1).siblings().fadeTo(200, 0.6)
            
            // $('#img').data('currentIndex', $li.data('index')).trigger('image-loaded')
            
            $('#img')
                .trigger('image-loaded');
                /*.hover(
                    function(){ $('#img .caption').stop().animate({height: 25}, 250) },
                    function(){ $('#img .caption').stop().animate({height: 0}, 250) 
                    }
                );*/
        },
        
        // function similar to onImage, but fired when thumbnail is displayed
        onThumb: function(thumb) {
            var $li = thumb.parent(),
                opacity = $li.is('.active') ? 1 : 0.6;
            
            // hover effects for list elements
            $li.hover(
                function() { $li.fadeTo(200, 1); },
                function() { $li.not('.active').fadeTo(200, opacity); }
            )
        }        
    }).find('li:first').addClass('active'); // display first image when Galleria is loaded
    
});


