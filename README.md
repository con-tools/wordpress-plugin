# ConTroll Wordpress Plugin

The ConTroll Wordpress plugin allows a wordpress site to integrate with the ConTroll convention management
system and to have the convention website hosted on Wordpress with full support for event planning, registration,
ticket and merchandise purchasing.

## Installation

1. Copy the contents the `con-troll` folder to the Wordpress `plugins` directory.
1. Log in to the Wordpress site, and under Settings -> ConTroll, put in your convention's ID and secret (that
  you got from the ConTroll management system)
1. Use the ConTroll templates and short codes in your Wordpress Pages
1. Make sure to create the following pages and update the ConTroll settings page with their URLs:
    * Shopping cart page
    * User's personal agenda page (optional - if not set, the shopping cart page will be used)
    * Single event page (used by the shopping cart page to link to events)
1. Optional - but recommended - create a page for the user log in flow using the Controll User Login/Passwords template

## Templates

The ConTroll plugin initially offered most of its implementation through the addition of Wordpress page templates
for use in the Wordpress page editor.

Templates are mostly deprecated, though they do offer some capabilities that are hard to achieve otherwise, their
use is technically difficult, the affect the layout of your pages in ways that may not be agreeable to you and they
limit the things you can do with the page. None-the-less, for some complex workflows, they are still required - at
least until we figure out how to do these things on standard Wordpress templates.

### Login and Authentication Management

An important workflow is login and authentication management - as part of ConTroll operations, users will need to log
in with the ConTroll convention managment system to be able to register to events and buy tickets, passes and merchandise.

ConTroll offers a rich set of APIs to handle log ins, support both username/password log ins as well as authorization
through Google, Facebook and Twitter (additional providers can be offered, ping ConTroll team for details).

To integrate these workflows with your convention website, make sure to create a page using the `ConTroll User Login/Passwords`
template. This page allows users who are using the username/password log in system to register, change their passwords
or recover lost passwords.

This template displays the content of the page when loaded normally, and offers work flows for log out, password registration
and password reset using the request argument `action` and one of the suppored values.

It is recommended that the content of the page will offer users links to trigger the workflows you want supported on your
site. The following example exposes all the workflows as well as direct login using supported authentication providers:

```
[controll-if-auth]
<a href="?action=logout">Logout</a>
[/controll-if-auth]
[controll-unless-auth]
Login using:
<div id="google">[controll-login-link provider="google"]<img src="http://api.con-troll.org/images/auth/google/btn_google_signin_dark_normal_web.png">[/controll-login-link]</div>
<div id="twitter">[controll-login-link provider="twitter"]<img src="http://api.con-troll.org/images/auth/twitter/twitter_login.png">[/controll-login-link]</div>
<div id="facebook">[controll-login-link provider="facebook"]<img src="http://api.con-troll.org/images/auth/facebook/facebook-login-with.png">[/controll-login-link]</div>

Or with an email and password:
[controll-login-form]
<p><input type="email" name="email" id="email" placeholder="E-Mail"></p>
<p><input type="password" name="password" id="password" placeholder="Password"></p>
<p><button class="small" type="submit">כניסה</button></p>
[/controll-login-form]

<a href="?action=register">Register as a new user with email and password</a>

<a href="?action=needreset">Reset password</a>
[/controll-unless-auth]
```

### User Activity / Shopping Cart

In order to complete payment on tickets, passes or merchandise, the ConTroll plugin offers a shopping cart page using
the `ConTroll User Page/Cart` template.

Designate a page on the website to use this template, then set up the address to this page in the ConTroll settings panel, 
so that registration routines can direct users to that page to complete their purchases.

The shopping cart page also doubles as a user activity summary page, listing all the tickets for events the user has
registered to, passes they acquired and merchandise they bought. Additionally, if the user is hosting events - these
will also be listed in their own section - making this page a one-stop-shop for the user to get their agenda for the
convention.

This template requires no text content to be added to the page, but if any text
is added, it will be rendered before the shopping cart HTML. It might be useful
to put in some sort of header and explanation text there.

### Merchandise Shop

The ConTroll convention management system allows you to set up a simple merchandise shop as part of your website to sell
some con-swag such as shirts, mugs etc. After setting up the shop's items for sale using the ConTroll management system,
set up a page on your website with the template `ConTroll Shop`, to display the shop's content to the users and allow them to add these
items to their shopping cart.

This template requires no text content to be added to the page.

### ConTroll Event List

The ConTroll page template `ConTroll Event List` allows you to set up a list of all the time slots (scheduled events)
available in the convention. This template automatically generates the list, allows the user to filter by certain criteria,
then calls the page content to render the specific scheduled time slots.

After setting up a page with this template, add content to the page to render a single scheduled event time slot, using
ConTroll short codes (see below). The template will render the content once for each scheduled event time slot that is
to be displayed. The object being sent to the content is the `Timeslot` object.

This template type is deprecated. Instead of using it, you can create a standard page using any template of your choice
and use the ConTroll short code tag `[controll-list-repeat]` with a `source` attribute set to `timeslots` (see below
for detailed short code documentation).

### ConTroll Event Registration Page

The ConTroll page template `ConTroll Event` is used to render a single scheduled event time slot, display more details
on the specific schedule (at the editor's convenience - all the data used in this page is also available in the Event List
page) and allow the users to register for this event.

After setting up a page with this template, add content to the page to render the scheduled event time slot, using
ConTroll short codes (see below). The template will render the content for the selected scheduled event time slot. The
object being sent to the content is the `Timeslot` object.

### ConTroll Daily Pass Page

This page can be used to allow users to buy daily passes to the convention (if the convention is set up to use daily passes
instead of purchasing event tickets separately). This page is not available as a template, instead use the ConTroll short code
tag `[controll-list-repeat]` with a `source` attribute set to `passes` (see below for detailed short code documentation).

## Short Codes

The ConTroll plugin offers editors to render most of the ConTroll features directly into their pages using special Wordpress
"short codes". At this point these short codes are not available from the editor toolbar and editors have to type them 
directly into the editor. Please pay attention to the formatting as it is very strict. Also, in case you are writing a
Right-To-Left formatted page (some times called BiDi or BiDirectional page), it is recommended to edit the short codes in
Left-To-Right mode to make sure you don't miss quotes or put in brackets on the wrong site. The Wordpress toolbar button
"Switch Direction" should come in handy for that.

### `[controll-list-repeat]`

The entry point for most ConTroll rendering, this short code tags allows the editor to access data sources both made available
by a ConTroll template as well as to load data directly from the ConTroll server. This short code will access a list of items
(such as scheduled events time slots, passes, merchandise available, etc) and render the content inside the short code once
for each such item.

The content of the short code will be parsed for ConTroll Expression Language expressions (see below for details).

Please note that Wordpress does not allow nesting of multiple instances of the same short code. So if you want to use this
short code to iterate over a list, and in each item - render an additional list using `[controll-list-repeat]`, 
then that will not work. In order to support nested iteration, we offer multiple copies of this short code by tacking on a
number at the end of the short code name. So to nest one list inside another, the internal list should use `[controll-list-repeat-1]`,
and if another list should be nested in side that, use `[controll-list-repeat-2]` and so fourth.

#### Attributes

* `path` - The object path (see below) to the list to be iterated on. This can be `.` (a single period) if the 
  current object is the needed list, or any other valid object path.
* `source` - Load data directly from the ConTroll event server. See Supported Sources below for a full list of
  valid values. When using `source`, the `path` attribute will be ignored and the loaded list will be used.
* `delimiter` - The provided text will be rendered between the list items (but not before the first item or
  after the last item). This can be used to add commas between names in the list or any other pure text (or HTML)
  that is needed. If not provided, the short code uses a single space character.

#### Supported Sources

For detailed descriptions of the objects provided by these sources and their fields, see Object Models below.

* `timeslots` - the list of scheduled event time slots. The objects provided are `Timeslot` objects. Only time slots
  that are in the "`Scheduled`" state are available.
* `locations` - the list of event locations. The objects provided are `Location` objects.
* `passes` - If the convention is set up to use daily passes, the list of configured daily passes. The objects provided
  are `Pass` objects.
* `user-passes` - If the convention is set up to use daily passes, the list of passes purchased by the current user.
  The objects provided are `UserPass` objects.
* `tickets` - The tickets to scheduled events registered by the user. Note: if the convention is *not* using daily passes,
  so that tickets have to be bought, then this list contains only tickets that have been bought and not tickets in the
  shopping cart that have not been paid for yet. The objects provided are `Ticket` objects.
* `hosting` - The list of scheduled event time slots the current user is hosting. The objects provided are `Timeslot`
  objects.
* `purchases` - the list of merchandise purchases the user has bought. The objects provided are `Purchase` objects.
* `coupons` - the list of coupons available for the current user. This does not include coupons previously used. The
  objects provided are `Coupon` objects.

### `[controll-date-format]`

Deprecated - use the Expression Language `date` filter (see below)

Format a date using PHP date format.

#### Attributes

* `path` - object path to read the date time from, under the "current object"
* `format` - [PHP date format](http://php.net/manual/en/function.date.php) to use to render this date time

### `[controll-register-button]`

Show a "register for event" button on the event page or inside a `Timeslot` list item. The register button is an HTML
button and can be customized by assigning it a CSS class using an attirbute. The button has 3 distinct modes:

* *Registration disabled* - when registration is not open yet (the "*Open Registration*" checkbox in the ConTroll
  settings panel is unchecked) the button shows "Registration is not open" and is disabled (its appearance can be
  customized by hooking to the `:disabled` CSS pseudo-class for the button. The text for this can be customized
  using an attribute (see below).
* *Log in required* - when registration is open (using the "*Open Registration*" checkbox in the ConTroll settings
  panel), but the user is not logged in to the ConTroll system, the button will be active and display the "Login"
  text. Clicking on the button will take the user through the ConTroll log in flow. The text for this mode can be
  customized using an attribute (see below).
* *Register* - when registration is open (using the "*Open Registration*" checkbox in the ConTroll settings
  panel) and the user is logged in, the button will allow the user to register for the event. The text for this mode
  can be customized using an attribute (see below).

If the convention uses ticket sales, then that's all there is to know about the register button - when the user clicks
"Register", the button will reserve a ticket for them for that event and move them to the checkout page where they can
either pay for their ticket or continue shopping for more stuff.

If the convention uses daily passes, then the registration function should allow the user to select the pass they want
to use for this event, or purchase a new pass - if they don't have one available. For this feature, clicking the button
opens an HTML pop up dialog (not a window) with a list of passes already owned by the user and the option buy a new pass.
The user then must then choose either an existing pass - if available (passes that cannot be used for the current event
due to a timing conglict - a pass can only be used once concurrently - are shown as "unavailable") or to choose to buy
a new pass. If they choose an available pass then a new registration ticket for this event will be created and the user
will be forwarded to the *User Page* where they can see their new ticket. If on the other hand the user chooses to buy
a new pass, then a new pass will be reserved for them as well as a registration ticket for the event using the new pass,
and they will be taken to the *Shopping Cart Page* so they can complete the purchase of the new pass. Once the purchase
is complete, the registration ticket will also automatically activate.

While the pass selection dialog has some default look and text, it is expected that the editor will want to customize it
to fit the convention web site look and language. This can be done by adding a ConTroll template as the content for the
short code. In the content, the current object is the `Timeslot` object and in addition it offers a list of user owned
passes as the field `passes` which contains a list of `UserPass` objects.

The default content of the dialog, which can be used as a reference example template, is as follows:

```
<p>Please choose a daily pass</p>
<table>
<thead>
	<tr><th>Name</th><th>Pass</th></tr>
</thead>
<tbody>
[controll-list-repeat path="passes"]
	<tr>
		<td>{{name}}</td><td>{{pass.title}}</td>
		<td>
		<span style="display:{{available|exist(inline)}}{{available|unless(none)}}">
			<button class="small" type="submit" name="pass" value="{{id}}">Register</button>
		</span>
		<span style="display:{{available|exist(none)}}{{available|unless(inline)}}">
			<button class="small" disabled="disabled">Not available</button>
		</span>
		</td>
	</tr>
[/controll-list-repeat]
</tbody>
<tbody>
	<tr>
		<td><input type="text" name="pass-name" value="" placeholder="Owner name" pattern=".+" error-text="Owner name is required"></td>
		<td><select name="pass-type">
			[controll-list-repeat source="passes"]
				<option value="{{id}}">{{title}}: ¤{{price}}</option>
			[/controll-list-repeat]
			</select></td>
		<td><button class="controll-popup-button" type="submit" name="pass" value="new">
			<i class="fa fa-shopping-cart"></i> Acquire a new pass
			</button></td>
	</tr>
</tbody>
</table>
```

#### Attributes

* `inactive-text` : override the button text for when registration isn't open. Default: "Registration isn't open".
* `login-text` : override the button text for when registration is open and user isn't logged in. Default: "Login"
* `register-text` : override the button text for when registration is open and the user is logged in. Default: "Register".
* `class` : CSS class to assign the button for styling

### `[controll-buy-pass]`

Shows the button for buying daily passes (for use with conventions that use daily passes) with the pop up dialog that
is used to enter the pass owner name. The button handles the buying and redirects to the *Shopping Cart Page* if there
are no errors, to allow the user to complete the registration process

#### Attributes

* `buy-text` : Override the text on the purchase button (the button that shows the name dialog).
* `name-prompt` : Override the text to show for the label prompting the user to enter the pass owner name
* `conform` : Override the text of the "OK" button confirming the purchase

### `[controll-verify-auth]`

Verify that the current user is logged in. Place at the top of pages that require a user to be logged in to the
ConTroll convention management system - for example, the user's personal convention agenda page.

### `[controll-user]`

Display details about the current user that is logged in to the ConTroll convention management system. If no user is
currently logged in, this shortcode behaves like `[controll-verify-auth]`, but if a logged in user is logged in, 
this shortcode will intead render the user's full name.

### `[controll-if-auth]`

Render the content inside this short  code, if the user is logged in to the ConTroll convention management system.
This is a good tag to use for the logout button (using the ConTroll user flow page template).

## `[controll-unless-auth]`

Render the content inside this short  code, if the user is not logged in to the ConTroll convention management system.
This is a good tag to use for giving users options to log in.

## `[controll-login-link]`

Helper shortcode to create a link for letting a user log in using ConTroll and
a supported third-party authentication provider. Use this shortcode instead of an
`<a>` tag - the shortcode will automatically construct the correct ConTroll auth
URL and supports a `redirect-url` query string parameter as sent by some other
ConTroll automated login processes.

### Attributes

* `provider` : The third-party authentication provider that will be used to handle
  the user authentication. Currently supported valid values: "`google`",
  "`facebook`" and "`twitter`"
* `default-page` : the page to move the user to when authentication is completed,
  if `redirect-url` is not set (i.e. not as part of the ConTroll automatic login
  process). Default to the "user page" configured in the ConTroll settings.
* `class` : CSS class to assign to the created tag. Defaults to `controll-login`

## `[controll-login-form]`

Helper shortcode to create a username/password login form for use with ConTroll
password logins. Use this shortcode instead of a `<form>` tag - the shortcode will
automatically construct the correct ConTroll auth URL and supports a `redirect-url`
query string paramater as sent by some other ConTroll automated login processes.

### Attributes

* `default-page` : the page to move the user to when authentication is completed,
  if `redirect-url` is not set (i.e. not as part of the ConTroll automatic login
  process). Default to the "user page" configured in the ConTroll settings.
* `class` : CSS class to assign to the created tag. Defaults to `controll-login`

## ConTroll Expression Language

In order to easily render ConTroll data into wordpress pages, ConTroll short codes that offer text rendering (e.g. 
`[controll-list-repeat]` and others) will understand special expressions embedded in the text and will replace these
with content loaded from the ConTroll object model (see below for details).

An expression is made up of an opening delimiter using double curly braces (`{{`) followed by an object path to the
content to be displayed, then a filter can be addedd by tacking on a pipe (`|`) followed by the filter expression, and
finally a closing delimiter using double curly braces (`}}`). Examples:

```
{{title}}
```

```
{{event.start_time|date(d/n H:i)}}
```

### Object Paths

Often the object being rendered by the short code is a complex object with multiple fiels, some of which will point to
other objects instead of simple text or numbers. To access these "nested objects", the expression language supports 
"object paths" - a description of how to "travel" inside the object structure to get at the content that you want to
display.

Object paths are made of a sequence of fields delimited by periods. For example, given this sample object:

```
{
  title: "my character",
  items: [
    {
      name: "sword"
    },
    {
      name: "bow"
    }
  ],
  player: {
    name: "John Smith",
    age: 15
  }
}
```

Then object paths may be used to access any part of the object. Some examples:

* The character's title: `{{title}}`
* The first item: `{{items.0}}` (counting in lists is "0-based")
* The player's age: `{{player.age}}`

Objects paths may also be used in short code attributes to tell a short code to access specific data. In both cases
(short code attributes or expressions in rendered text) the special path `.` (a single period) can be used to access
the "current object".

### Filters

The following filters are supported by the expression language:

#### `date(<date-format>)`

The `date` filter allows the editor to format dates and times as they see fit. If the field to be displayed is a date-time
field, the `date` filter will render it according to the provided `<date-format>` (which must be enclosed in parenthesis)
according to the [PHP date format specification](http://php.net/manual/en/function.date.php).

Exmaple:

```
{{start_time|date(d/n H:i)}}
```

#### `exist(<text>)` and `unless(<text>)`

If the field being accessed is a boolean value (can have only the values `true` or `false`), the `exist` and `unless`
filters can be used to render text if the value is `true` or `false` (respectively). The text inside the parenthesis is
rendered as is without additional expression language parsing.

Example:
```
{{event.requires-registration|unless(This event is free to enter)}}
```

## Object Models

ConTroll short codes and the expression language allows the editor access to parts of the ConTroll data as required. Short codes
and templates will make available objects of the following types (see the specific template and short code documentation to
understand which type of objects are available where).

### `Timeslot`

Represents a scheduling of an event - i.e. the time and location a specific event is scheduled to happen.

Fields:

* `id` : The ConTroll ID for the scheduled event time slot
* `duration` : The duration of the event in minutes
* `min-attendees` : Minimum number of attendees for this schedule
* `max-attendees` : Maximum number of attendees for this schedule
* `notes-to-attendees` : Text notes for the attendees to read
* `event` : The `Event` object (see below) of the master event for this scheduled time
* `start` : "2017-04-06T16:00:00+00:00",
* `hosts` : A list of `User` objects, one for each of the scheduled event hosts
* `available_tickets` : The number of tickets currently available for this event - i.e. max attendees minus the number
  of users already registered
* `locations` : A list of `Location` objects, one for each of the locations where this event is scheduled to.
* `status` : Whether the event is scheduled or cancelled. In the ConTroll wordpress plugin this will always have the
  value `scheduled`.

### `Event`

Represents an event that can be scheduled. Multiple scheduling of the same event can happen.

Fields:

* `id` : The ConTroll ID for the event
* `title` : The title of the event
* `teaser` : The teaser text of the event
* `description` : The full text description of the event,
* `price` : The price specified for tickets for this event (if the convention does not use daily passes)
* `requires-registration` : Whether this event requires attendees to register or if its free to access
* `duration` : The duration of the event in minutes
* `min-attendees` : Minimum number of attendees for such an event
* `max-attendees` : Maximum number of attendees for such an event
* `notes-to-attendees` : Text notes for the attendees to read
* `custom-data` : Custom data entered into the ConTroll management system for this event
* `status` : The event status as a progressing number (0 to 6)
* `status-text` : The textual description of the event status, either of the following text (in order):
  `submitted`,`has teaser`, `content approved`, `logistics approved`, `scheduled`, `approved`, `cancelled`;
* `user` : The `User` object (See below) of the owner / submitter of this event
* `staff-contact` : The `User` object of the convention staff member who is responsible for this event
* `tags` : A set of convention tags associated with this event. The tags each have a name and value (or a list of
  values for tags set in the ConTroll management system as "one or more"). To access specific tags using expression
  language, use the name of a tag as part of the object path. For example: `{{event.tags.genre}}`

### Location

Represents a location where an event may be scheduled to happen.

Fields:

* `title` : The title for the location
* `slug` : The location slug (short "URL safe" text)
* `max-attendees` : The maximum number of attendees this location can hold
* `area` : General area where this location can be found, such as floor number.

### Pass

Represents a daily pass that a user can buy.

Fields:

* `id` : The ConTroll ID for this pass
* `title` : The title of this pass
* `slug` : The sluf for this pass (short "URL safe" text);
* `price` : price of this pass

### UserPass

Represents a purchased pass bought for a user.

Fields:

* `id`: the ConTroll ID for this purchased pass.
* `status` : the purchase status. Can be one of `reserved`, `processing`, `authorized`, `cancelled` or `refunded`.
* `name` : the person's name who will be using this purchased pass.
* `price` : the price at which this pass was purchased at (can be lower than the original pass price if the user
  has used coupons or discounts for the purchase).
* `reserved-time` : the time at which the pass was ordered.
* `user` : The `User` object (see below) for the user who bought this pass.
* `pass` : The `Pass` object (see above) for the pass that was purchased.

### Ticket

Represents a single registration of a user to a scheduled event. If the convention does not use passes, then this also is
what the user is paying for when they register for an event.

Fields:

* `id`: the ConTroll ID for this ticket
* `status` : the purchase status. Can be one of `reserved`, `processing`, `authorized`, `cancelled` or `refunded`.
  If the convention uses passes, only `reserved`, `authorized` and `cancelled` are valid. 
* `amount` : amount of tickets purchased. Relevant only if the convention does not use daily passes, otherwise each ticket
  has to be associated with a single user pass, as a result the amount is always 1.
* `price` : the price at which this ticket was purchased at (can be lower than the original event price if the user
  has used coupons or discounts for the purchase). If the convention is using daily passes, this will always be 0.
* `reserved-time` : the time at which the ticket  was ordered.
* `user` : The `User` object (see below) for the user who registered this ticket.
* `pass` : If the convention is using daily passes, this will be set to the `Pass` object (see above) for the pass that
  was used to register to the event.
* `timeslot` : The `Timeslot` object of the scheduled event time slot that this ticket is registered to.

### Purchase

Represents a purchase a user has made from the merchandise store.

Fields:

* `id` : The ConTroll ID for this purchase.
* `amount` : The number of items of the merchandise SKU that were purchased.
* `price` : The price at which this purchase was made at (can be lower than the expected item price, if the user has used
  coupons or discounts for the purhcase).
* `status` : The purchase status. In the ConTroll Wordpress plugin this is always `authorized`.
* `reserved-time` : The time at which this purchase was ordered.
* `sku` : The `SKU` object (see below) for the merchandise item that was purchased.
* `user` : The `User` object (see below) for the user who purchased these items.

### User

Represents a registered user in the ConTroll convention management system. Used for referencing to users who registered to
events or are hosting them, and such.

Fields:

* `id` : The ConTroll ID for this user
* `name` : The name of the user
* `email` : The email address of the user
* `phone` : The phone number for the user

### Coupon

Represents discount coupons available to the user for discounting purchases, tickets and passes.

Fields:

* `id` : The ConTroll ID for this coupon
* `value`: The value of the coupon
* `user`: The `User` object (see above) for the user who owns this coupon
* `type`: The coupon type object for this coupon (see below)
* `used`: boolean value whether this coupon has already been used (always false) 

### Coupon Type

Represents a class of coupons that may be awarded to users.

Fields:

* `id` : The ConTroll ID for this coupon type
* `title` : The coupon type title - text specifying what type of coupon this is
* `category` : The coupon type category title - text specifying the category of coupons this belongs to
* `code` : coupon type code - text specifying the SKU code or some other code associated with this type of coupons
