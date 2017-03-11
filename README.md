# ConTroll Wordpress Plugin

The ConTroll Wordpress plugin allows a wordpress site to integrate with the ConTroll convention management
system and to have the convention website hosted on Wordpress with full support for event planning, registration,
ticket and merchandise purchasing.

## Installation

1. Copy the contents the `con-troll` folder to the Wordpress `plugins` directory.
1. Log in to the Wordpress site, and under Settings -> ConTroll, put in your convention's ID and secret (that
  you got from the ConTroll management system)
1. Use the ConTroll templates and short codes in your Wordpress Pages

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

This template requires no text content to be added to the page.

### User Activity / Shopping Cart

In order to complete payment on tickets, passes or merchandise, the ConTroll plugin offers a shopping cart page using
the `ConTroll User Page/Cart` template. 

Designate a page on the website to use this template, then set up the address to this page in the ConTroll settings panel, 
so that registration routines can direct users to that page to complete their purchases.

The shopping cart page also doubles as a user activity summary page, listing all the tickets for events the user has
registered to, passes they acquired and merchandise they bought. Additionally, if the user is hosting events - these
will also be listed in their own section - making this page a one-stop-shop for the user to get their agenda for the
convention.

This template requires no text content to be added to the page.

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

### `[controll-date-format]`

Deprecated - use the Expression Language `date` filter (see below)

Format a date using PHP date format.

#### Attributes

* `path` - object path to read the date time from, under the "current object"
* `format` - [PHP date format](http://php.net/manual/en/function.date.php) to use to render this date time

### `[controll-handle-buy-pass]`

Handle the daily pass purchase work flow - you must put this short code at the top of the daily pass store page.

#### Attributes

* `success-page` : Override the page the user will be directed to after reserving a daily pass. Defaults to
  the ConTroll shopping cart page as specified in the ConTroll plugin settings.
* `pass` : Override the name of the pass reservation form field that holds the pass ID to reserve. Defaults to `pass`.
* `name-field` : Override the name of the pass reservation form field that holds the pass owner name to reserve.
  Defaults to `name`.

### `[controll-verify-auth]`

Verify that the current user is logged in. Place at the top of pages that require a user to be logged in to the
ConTroll convention management system - for example, the user's personal convention agenda page.

### `[controll-user]`

Display details about the current user that is logged in to the ConTroll convention management system. If no user is
currently logged in, this shortcode behaves like `[controll-verify-auth]`, but if a logged in user is logged in, 
this shortcode will intead render the user's full name.

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

* `name` : The name of the user
* `email` : The email address of the user
* `phone` : The phone number for the user
