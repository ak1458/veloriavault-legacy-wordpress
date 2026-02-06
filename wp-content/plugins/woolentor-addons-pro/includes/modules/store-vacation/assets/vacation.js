(function($){
    'use strict';

    // Configuration object for easy maintenance
    const CONFIG = {
        selectors: {
            countdown: '.woolentor-vacation-countdown',
            popup: '.woolentor-vacation-popup',
            popupClose: '.woolentor-popup-close',
            floatingNotice: '.woolentor-store-vacation-notice.floating-notice',
            noticeClose: '.notice-close'
        },
        timeUnits: {
            day: 24 * 60 * 60 * 1000,
            hour: 60 * 60 * 1000,
            minute: 60 * 1000,
            second: 1000
        }
    };

    // Helper function to calculate time difference
    function calculateTimeDifference(endDateTime) {
        const now = new Date().getTime();
        const distance = endDateTime - now;
        
        if (distance < 0) return null;

        return {
            days: Math.floor(distance / CONFIG.timeUnits.day),
            hours: Math.floor((distance % CONFIG.timeUnits.day) / CONFIG.timeUnits.hour),
            minutes: Math.floor((distance % CONFIG.timeUnits.hour) / CONFIG.timeUnits.minute),
            seconds: Math.floor((distance % CONFIG.timeUnits.minute) / CONFIG.timeUnits.second)
        };
    }

    // Update countdown display
    function updateCountdownDisplay($element, timeData) {
        if (!timeData) return false;
        
        $element.find('.woolentor-days').text(timeData.days);
        $element.find('.woolentor-hours').text(timeData.hours);
        $element.find('.woolentor-minutes').text(timeData.minutes);
        $element.find('.woolentor-seconds').text(timeData.seconds);
        
        return true;
    }

    function initCountdown() {
        $(CONFIG.selectors.countdown).each(function(){
            const $this = $(this);
            const endDate = $this.data('end-date');
            
            if (!endDate) {
                console.warn('Countdown end date not specified');
                return;
            }

            const endDateTime = new Date(endDate).getTime();
            if (isNaN(endDateTime)) {
                console.error('Invalid end date format');
                return;
            }

            // Initial update
            const initialTime = calculateTimeDifference(endDateTime);
            if (updateCountdownDisplay($this, initialTime)) {
                $this.show();
            }

            // Start countdown
            const countdown = setInterval(() => {
                const timeData = calculateTimeDifference(endDateTime);
                
                if (!updateCountdownDisplay($this, timeData)) {
                    clearInterval(countdown);
                    $this.hide();
                }
            }, 1000);
        });
    }

    function initPopup() {
        const $popup = $(CONFIG.selectors.popup);
        
        if ($popup.length) {
            // Show popup with animation after a short delay
            setTimeout(() => {
                $popup.fadeIn();
            }, 500);

            // Close popup handlers
            $(CONFIG.selectors.popupClose).on('click', function(e) {
                e.preventDefault();
                $popup.fadeOut();
            });

            // Close on escape key
            $(document).on('keyup', function(e) {
                if (e.key === 'Escape' && $popup.is(':visible')) {
                    $popup.fadeOut();
                }
            });
        }
    }
    

    function initFloatingNotice() {
        const $floatingNotice = $(CONFIG.selectors.floatingNotice);
        
        if ($floatingNotice.length) {
            // Show floating notice with animation
            $floatingNotice.hide().fadeIn(1000);

            // Close button handler
            $floatingNotice.find(CONFIG.selectors.noticeClose).on('click', function() {
                $(this).closest(CONFIG.selectors.floatingNotice).fadeOut();
            });
        }
    }

    // Initialize all components when document is ready
    $(document).ready(function(){
        try {
            initCountdown();
            initPopup();
            initFloatingNotice();
        } catch (error) {
            console.error('Error initializing vacation components:', error);
        }
    });

})(jQuery);