// @see http://stackoverflow.com/questions/290254/how-to-order-events-bound-with-jquery

(function($)
{
    $.fn.bindFirst = function(/*String*/ eventType, /*[Object])*/ eventData, /*Function*/ handler)
    {
        var indexOfDot = eventType.indexOf(".");
        var eventNameSpace = indexOfDot > 0 ? eventType.substring(indexOfDot) : "";

        eventType = indexOfDot > 0 ? eventType.substring(0, indexOfDot) : eventType;
        handler = handler == undefined ? eventData : handler;
        eventData = typeof eventData == "function" ? {} : eventData;

        return this.each(function()
        {
            var $this = $(this);
            var currentAttrListener = this["on" + eventType];

            if (currentAttrListener)
            {
                $this.bind(eventType, function(e)
                {
                    // Note line below did have a bug before 06/07/2011
                    // It was not returning the listener's result.
                    return currentAttrListener(e.originalEvent); 
                });

                this["on" + eventType] = null;
            }

            $this.bind(eventType + eventNameSpace, eventData, handler);

            var allEvents = $this.data("events");
            var typeEvents = allEvents[eventType];
            var newEvent = typeEvents.pop();
            typeEvents.unshift(newEvent);
        });
    };
})(jQuery);