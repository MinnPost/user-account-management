'use strict';

var $ = window.jQuery;

function showPassword() {
    // Cache our jquery elements
    var $submit = $('.btn-submit');
    var $field = $('.password-show');
    var show_pass = '<div class="a-form-show-password a-form-caption"><label><input type="checkbox" name="show_password" id="show-password-checkbox" value="1"> Show password</label></div>';
    // Inject the toggle button into the page
    $field.after(show_pass);
    // Cache the toggle button
    var $toggle = $('#show-password-checkbox');
    // Toggle the field type
    $toggle.on('click', function (e) {
        var checkbox = $(this);
        if (checkbox.is(':checked')) {
            $field.attr('type', 'text');
        } else {
            $field.attr('type', 'password');
        }
    });
    // Set the form field back to a regular password element
    $submit.on('click', function (e) {
        $field.attr('type', 'password');
    });
}

function checkPasswordStrength($password, $strengthResult, $submitButton, blacklistArray) {
    var password = $password.val();

    // Reset the form & meter
    $submitButton.attr('disabled', 'disabled');
    $strengthResult.removeClass('short bad good strong');

    // Extend our blacklist array with those from the inputs & site data
    blacklistArray = blacklistArray.concat(wp.passwordStrength.userInputBlacklist());

    // Get the password strength
    var strength = wp.passwordStrength.meter(password, blacklistArray, password);

    // Add the strength meter results
    switch (strength) {
        case 2:
            $strengthResult.addClass('bad').html(pwsL10n.bad);
            break;
        case 3:
            $strengthResult.addClass('good').html(pwsL10n.good);
            break;
        case 4:
            $strengthResult.addClass('strong').html(pwsL10n.strong);
            break;
        case 5:
            $strengthResult.addClass('short').html(pwsL10n.mismatch);
            break;
        default:
            $strengthResult.addClass('short').html(pwsL10n.short);
    }

    // Only enable the submit button if the password is strong
    if (4 === strength) {
        $submitButton.removeAttr('disabled');
    }

    return strength;
}

$(document).ready(function () {
    showPassword();
    // checkPasswordStrength
    var $before = $('.a-form-show-password');
    $before.after($('<span id="password-strength"></span>'));
    $('body').on('keyup', 'input[name=password], input[name=new_password]', function (event) {
        checkPasswordStrength($('input[name=password], input[name=new_password]'), // First password field
        $('#password-strength'), // Strength meter
        $('input[type=submit]'), // Submit button
        ['black', 'listed', 'word'] // Blacklisted words
        );
    });
});
//# sourceMappingURL=data:application/json;charset=utf8;base64,eyJ2ZXJzaW9uIjozLCJzb3VyY2VzIjpbInVzZXIuanMiXSwibmFtZXMiOlsiJCIsIndpbmRvdyIsImpRdWVyeSIsInNob3dQYXNzd29yZCIsIiRzdWJtaXQiLCIkZmllbGQiLCJzaG93X3Bhc3MiLCJhZnRlciIsIiR0b2dnbGUiLCJvbiIsImUiLCJjaGVja2JveCIsImlzIiwiYXR0ciIsImNoZWNrUGFzc3dvcmRTdHJlbmd0aCIsIiRwYXNzd29yZCIsIiRzdHJlbmd0aFJlc3VsdCIsIiRzdWJtaXRCdXR0b24iLCJibGFja2xpc3RBcnJheSIsInBhc3N3b3JkIiwidmFsIiwicmVtb3ZlQ2xhc3MiLCJjb25jYXQiLCJ3cCIsInBhc3N3b3JkU3RyZW5ndGgiLCJ1c2VySW5wdXRCbGFja2xpc3QiLCJzdHJlbmd0aCIsIm1ldGVyIiwiYWRkQ2xhc3MiLCJodG1sIiwicHdzTDEwbiIsImJhZCIsImdvb2QiLCJzdHJvbmciLCJtaXNtYXRjaCIsInNob3J0IiwicmVtb3ZlQXR0ciIsImRvY3VtZW50IiwicmVhZHkiLCIkYmVmb3JlIiwiZXZlbnQiXSwibWFwcGluZ3MiOiI7O0FBQUEsSUFBSUEsSUFBSUMsT0FBT0MsTUFBZjs7QUFFQSxTQUFTQyxZQUFULEdBQXdCO0FBQ3ZCO0FBQ0EsUUFBSUMsVUFBVUosRUFBRSxhQUFGLENBQWQ7QUFDQSxRQUFJSyxTQUFTTCxFQUFFLGdCQUFGLENBQWI7QUFDQSxRQUFJTSxZQUFZLHdLQUFoQjtBQUNBO0FBQ0FELFdBQU9FLEtBQVAsQ0FBY0QsU0FBZDtBQUNBO0FBQ0EsUUFBSUUsVUFBVVIsRUFBRSx5QkFBRixDQUFkO0FBQ0E7QUFDQVEsWUFBUUMsRUFBUixDQUFXLE9BQVgsRUFBb0IsVUFBU0MsQ0FBVCxFQUFZO0FBQy9CLFlBQUlDLFdBQVdYLEVBQUUsSUFBRixDQUFmO0FBQ0EsWUFBSVcsU0FBU0MsRUFBVCxDQUFZLFVBQVosQ0FBSixFQUE2QjtBQUM1QlAsbUJBQU9RLElBQVAsQ0FBWSxNQUFaLEVBQW9CLE1BQXBCO0FBQ0EsU0FGRCxNQUVPO0FBQ05SLG1CQUFPUSxJQUFQLENBQVksTUFBWixFQUFvQixVQUFwQjtBQUNBO0FBQ0QsS0FQRDtBQVFBO0FBQ0FULFlBQVFLLEVBQVIsQ0FBWSxPQUFaLEVBQXFCLFVBQVNDLENBQVQsRUFBWTtBQUNoQ0wsZUFBT1EsSUFBUCxDQUFZLE1BQVosRUFBb0IsVUFBcEI7QUFDQSxLQUZEO0FBR0E7O0FBRUQsU0FBU0MscUJBQVQsQ0FBZ0NDLFNBQWhDLEVBQTJDQyxlQUEzQyxFQUE0REMsYUFBNUQsRUFBMkVDLGNBQTNFLEVBQTRGO0FBQ3hGLFFBQUlDLFdBQVdKLFVBQVVLLEdBQVYsRUFBZjs7QUFFQTtBQUNBSCxrQkFBY0osSUFBZCxDQUFvQixVQUFwQixFQUFnQyxVQUFoQztBQUNBRyxvQkFBZ0JLLFdBQWhCLENBQTZCLHVCQUE3Qjs7QUFFQTtBQUNBSCxxQkFBaUJBLGVBQWVJLE1BQWYsQ0FBdUJDLEdBQUdDLGdCQUFILENBQW9CQyxrQkFBcEIsRUFBdkIsQ0FBakI7O0FBRUE7QUFDQSxRQUFJQyxXQUFXSCxHQUFHQyxnQkFBSCxDQUFvQkcsS0FBcEIsQ0FBMkJSLFFBQTNCLEVBQXFDRCxjQUFyQyxFQUFxREMsUUFBckQsQ0FBZjs7QUFFQTtBQUNBLFlBQVNPLFFBQVQ7QUFDSSxhQUFLLENBQUw7QUFDSVYsNEJBQWdCWSxRQUFoQixDQUEwQixLQUExQixFQUFrQ0MsSUFBbEMsQ0FBd0NDLFFBQVFDLEdBQWhEO0FBQ0E7QUFDSixhQUFLLENBQUw7QUFDSWYsNEJBQWdCWSxRQUFoQixDQUEwQixNQUExQixFQUFtQ0MsSUFBbkMsQ0FBeUNDLFFBQVFFLElBQWpEO0FBQ0E7QUFDSixhQUFLLENBQUw7QUFDSWhCLDRCQUFnQlksUUFBaEIsQ0FBMEIsUUFBMUIsRUFBcUNDLElBQXJDLENBQTJDQyxRQUFRRyxNQUFuRDtBQUNBO0FBQ0osYUFBSyxDQUFMO0FBQ0lqQiw0QkFBZ0JZLFFBQWhCLENBQTBCLE9BQTFCLEVBQW9DQyxJQUFwQyxDQUEwQ0MsUUFBUUksUUFBbEQ7QUFDQTtBQUNKO0FBQ0lsQiw0QkFBZ0JZLFFBQWhCLENBQTBCLE9BQTFCLEVBQW9DQyxJQUFwQyxDQUEwQ0MsUUFBUUssS0FBbEQ7QUFkUjs7QUFpQkE7QUFDQSxRQUFLLE1BQU1ULFFBQVgsRUFBc0I7QUFDbEJULHNCQUFjbUIsVUFBZCxDQUEwQixVQUExQjtBQUNIOztBQUVELFdBQU9WLFFBQVA7QUFDSDs7QUFFRDFCLEVBQUVxQyxRQUFGLEVBQVlDLEtBQVosQ0FBa0IsWUFBVztBQUM1Qm5DO0FBQ0E7QUFDQSxRQUFJb0MsVUFBVXZDLEVBQUUsdUJBQUYsQ0FBZDtBQUNBdUMsWUFBUWhDLEtBQVIsQ0FBZVAsRUFBRSxzQ0FBRixDQUFmO0FBQ0dBLE1BQUcsTUFBSCxFQUFZUyxFQUFaLENBQWdCLE9BQWhCLEVBQXlCLGdEQUF6QixFQUNJLFVBQVUrQixLQUFWLEVBQWtCO0FBQ2QxQiw4QkFDSWQsRUFBRSxnREFBRixDQURKLEVBQ2lFO0FBQzdEQSxVQUFFLG9CQUFGLENBRkosRUFFdUM7QUFDbkNBLFVBQUUsb0JBQUYsQ0FISixFQUd1QztBQUNuQyxTQUFDLE9BQUQsRUFBVSxRQUFWLEVBQW9CLE1BQXBCLENBSkosQ0FJdUM7QUFKdkM7QUFNSCxLQVJMO0FBVUgsQ0FmRCIsImZpbGUiOiJ1c2VyLWFjY291bnQtbWFuYWdlbWVudC5qcyIsInNvdXJjZXNDb250ZW50IjpbInZhciAkID0gd2luZG93LmpRdWVyeTtcblxuZnVuY3Rpb24gc2hvd1Bhc3N3b3JkKCkge1xuXHQvLyBDYWNoZSBvdXIganF1ZXJ5IGVsZW1lbnRzXG5cdHZhciAkc3VibWl0ID0gJCgnLmJ0bi1zdWJtaXQnKTtcblx0dmFyICRmaWVsZCA9ICQoJy5wYXNzd29yZC1zaG93Jyk7XG5cdHZhciBzaG93X3Bhc3MgPSAnPGRpdiBjbGFzcz1cImEtZm9ybS1zaG93LXBhc3N3b3JkIGEtZm9ybS1jYXB0aW9uXCI+PGxhYmVsPjxpbnB1dCB0eXBlPVwiY2hlY2tib3hcIiBuYW1lPVwic2hvd19wYXNzd29yZFwiIGlkPVwic2hvdy1wYXNzd29yZC1jaGVja2JveFwiIHZhbHVlPVwiMVwiPiBTaG93IHBhc3N3b3JkPC9sYWJlbD48L2Rpdj4nO1xuXHQvLyBJbmplY3QgdGhlIHRvZ2dsZSBidXR0b24gaW50byB0aGUgcGFnZVxuXHQkZmllbGQuYWZ0ZXIoIHNob3dfcGFzcyApO1xuXHQvLyBDYWNoZSB0aGUgdG9nZ2xlIGJ1dHRvblxuXHR2YXIgJHRvZ2dsZSA9ICQoJyNzaG93LXBhc3N3b3JkLWNoZWNrYm94Jyk7XG5cdC8vIFRvZ2dsZSB0aGUgZmllbGQgdHlwZVxuXHQkdG9nZ2xlLm9uKCdjbGljaycsIGZ1bmN0aW9uKGUpIHtcblx0XHR2YXIgY2hlY2tib3ggPSAkKHRoaXMpO1xuXHRcdGlmIChjaGVja2JveC5pcygnOmNoZWNrZWQnKSkge1xuXHRcdFx0JGZpZWxkLmF0dHIoJ3R5cGUnLCAndGV4dCcpO1xuXHRcdH0gZWxzZSB7XG5cdFx0XHQkZmllbGQuYXR0cigndHlwZScsICdwYXNzd29yZCcpO1xuXHRcdH1cblx0fSk7XG5cdC8vIFNldCB0aGUgZm9ybSBmaWVsZCBiYWNrIHRvIGEgcmVndWxhciBwYXNzd29yZCBlbGVtZW50XG5cdCRzdWJtaXQub24oICdjbGljaycsIGZ1bmN0aW9uKGUpIHtcblx0XHQkZmllbGQuYXR0cigndHlwZScsICdwYXNzd29yZCcpO1xuXHR9KTtcbn1cblxuZnVuY3Rpb24gY2hlY2tQYXNzd29yZFN0cmVuZ3RoKCAkcGFzc3dvcmQsICRzdHJlbmd0aFJlc3VsdCwgJHN1Ym1pdEJ1dHRvbiwgYmxhY2tsaXN0QXJyYXkgKSB7XG4gICAgdmFyIHBhc3N3b3JkID0gJHBhc3N3b3JkLnZhbCgpO1xuXG4gICAgLy8gUmVzZXQgdGhlIGZvcm0gJiBtZXRlclxuICAgICRzdWJtaXRCdXR0b24uYXR0ciggJ2Rpc2FibGVkJywgJ2Rpc2FibGVkJyApO1xuICAgICRzdHJlbmd0aFJlc3VsdC5yZW1vdmVDbGFzcyggJ3Nob3J0IGJhZCBnb29kIHN0cm9uZycgKTtcblxuICAgIC8vIEV4dGVuZCBvdXIgYmxhY2tsaXN0IGFycmF5IHdpdGggdGhvc2UgZnJvbSB0aGUgaW5wdXRzICYgc2l0ZSBkYXRhXG4gICAgYmxhY2tsaXN0QXJyYXkgPSBibGFja2xpc3RBcnJheS5jb25jYXQoIHdwLnBhc3N3b3JkU3RyZW5ndGgudXNlcklucHV0QmxhY2tsaXN0KCkgKVxuXG4gICAgLy8gR2V0IHRoZSBwYXNzd29yZCBzdHJlbmd0aFxuICAgIHZhciBzdHJlbmd0aCA9IHdwLnBhc3N3b3JkU3RyZW5ndGgubWV0ZXIoIHBhc3N3b3JkLCBibGFja2xpc3RBcnJheSwgcGFzc3dvcmQgKTtcblxuICAgIC8vIEFkZCB0aGUgc3RyZW5ndGggbWV0ZXIgcmVzdWx0c1xuICAgIHN3aXRjaCAoIHN0cmVuZ3RoICkge1xuICAgICAgICBjYXNlIDI6XG4gICAgICAgICAgICAkc3RyZW5ndGhSZXN1bHQuYWRkQ2xhc3MoICdiYWQnICkuaHRtbCggcHdzTDEwbi5iYWQgKTtcbiAgICAgICAgICAgIGJyZWFrO1xuICAgICAgICBjYXNlIDM6XG4gICAgICAgICAgICAkc3RyZW5ndGhSZXN1bHQuYWRkQ2xhc3MoICdnb29kJyApLmh0bWwoIHB3c0wxMG4uZ29vZCApO1xuICAgICAgICAgICAgYnJlYWs7XG4gICAgICAgIGNhc2UgNDpcbiAgICAgICAgICAgICRzdHJlbmd0aFJlc3VsdC5hZGRDbGFzcyggJ3N0cm9uZycgKS5odG1sKCBwd3NMMTBuLnN0cm9uZyApO1xuICAgICAgICAgICAgYnJlYWs7XG4gICAgICAgIGNhc2UgNTpcbiAgICAgICAgICAgICRzdHJlbmd0aFJlc3VsdC5hZGRDbGFzcyggJ3Nob3J0JyApLmh0bWwoIHB3c0wxMG4ubWlzbWF0Y2ggKTtcbiAgICAgICAgICAgIGJyZWFrO1xuICAgICAgICBkZWZhdWx0OlxuICAgICAgICAgICAgJHN0cmVuZ3RoUmVzdWx0LmFkZENsYXNzKCAnc2hvcnQnICkuaHRtbCggcHdzTDEwbi5zaG9ydCApO1xuICAgIH1cblxuICAgIC8vIE9ubHkgZW5hYmxlIHRoZSBzdWJtaXQgYnV0dG9uIGlmIHRoZSBwYXNzd29yZCBpcyBzdHJvbmdcbiAgICBpZiAoIDQgPT09IHN0cmVuZ3RoICkge1xuICAgICAgICAkc3VibWl0QnV0dG9uLnJlbW92ZUF0dHIoICdkaXNhYmxlZCcgKTtcbiAgICB9XG5cbiAgICByZXR1cm4gc3RyZW5ndGg7XG59XG5cbiQoZG9jdW1lbnQpLnJlYWR5KGZ1bmN0aW9uKCkge1xuXHRzaG93UGFzc3dvcmQoKTtcblx0Ly8gY2hlY2tQYXNzd29yZFN0cmVuZ3RoXG5cdHZhciAkYmVmb3JlID0gJCgnLmEtZm9ybS1zaG93LXBhc3N3b3JkJyk7XG5cdCRiZWZvcmUuYWZ0ZXIoICQoJzxzcGFuIGlkPVwicGFzc3dvcmQtc3RyZW5ndGhcIj48L3NwYW4+JykpO1xuICAgICQoICdib2R5JyApLm9uKCAna2V5dXAnLCAnaW5wdXRbbmFtZT1wYXNzd29yZF0sIGlucHV0W25hbWU9bmV3X3Bhc3N3b3JkXScsXG4gICAgICAgIGZ1bmN0aW9uKCBldmVudCApIHtcbiAgICAgICAgICAgIGNoZWNrUGFzc3dvcmRTdHJlbmd0aChcbiAgICAgICAgICAgICAgICAkKCdpbnB1dFtuYW1lPXBhc3N3b3JkXSwgaW5wdXRbbmFtZT1uZXdfcGFzc3dvcmRdJyksICAgICAgICAgLy8gRmlyc3QgcGFzc3dvcmQgZmllbGRcbiAgICAgICAgICAgICAgICAkKCcjcGFzc3dvcmQtc3RyZW5ndGgnKSwgICAgICAgICAgIC8vIFN0cmVuZ3RoIG1ldGVyXG4gICAgICAgICAgICAgICAgJCgnaW5wdXRbdHlwZT1zdWJtaXRdJyksICAgICAgICAgICAvLyBTdWJtaXQgYnV0dG9uXG4gICAgICAgICAgICAgICAgWydibGFjaycsICdsaXN0ZWQnLCAnd29yZCddICAgICAgICAvLyBCbGFja2xpc3RlZCB3b3Jkc1xuICAgICAgICAgICAgKTtcbiAgICAgICAgfVxuICAgICk7XG59KTtcbiJdfQ==
