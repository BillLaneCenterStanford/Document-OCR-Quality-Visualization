/**
 *
 * Modified by Andrew Spode Miller (justfdi.com, @spode) on 9th December 2009 to include a "Delay" parameter.
 *
*/

/**
 * jQuery mousehold plugin - fires an event while a mouse click is held down, at a variable interval.
 *
 * Additionally, the function is passed a count of times the event has been fired in this session of mousehold.
 *
 * @author Remy Sharp (leftlogic.com)
 * @date 2006-12-15
 * @example $("img").mousehold(200, function(i){  }, 1000)
 * @desc Repeats firing the passed function while the mouse click is held down
 *
 * @name mousehold
 * @type jQuery
 * @param Number timeout The frequency to repeat the event in milliseconds
 * @param Function fn A function to execute
 * @param Number delay Milliseconds of delay before it should start firing
 * @cat Plugin
 */

jQuery.fn.mousehold = function(timeout, f, delay)
	{
	//Allows us to pass only one parameter
	if	(timeout && typeof timeout == 'function')
			{
			f = timeout;
			timeout = 100;
			}

	//If there is no delay, set a default
	if	(!delay)
			{
			var delay = 500;
			}

	//Only continue if it's been passed a function, this is infact a function
	if (f && typeof f == 'function')
		{
		var timer = 0;
		var delaytimer = 0;
		var fireStep = 0;

		return this.each(function()
				{
				jQuery(this).mousedown(function()
					{
					fireStep = 1;
					var ctr = 0;
					var t = this;

					delaytimer = setTimeout(function ()
						{
						timer = setInterval(function()
							{
							ctr++;
							f.call(t, ctr);
							fireStep = 2;
							}, timeout);
						}, delay);
					});

				//This makes sure that as we mouse out, or mouse up - all the timers are stopped
				clearMousehold = function()
					{
					clearInterval(timer);
					clearTimeout(delaytimer);
					if (fireStep == 1) f.call(this, 1);
					fireStep = 0;
					}
			
				jQuery(this).mouseout(clearMousehold);
				jQuery(this).mouseup(clearMousehold);
				});
		}
	}
