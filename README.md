NavBuilder
==========

A simple PHP utility class, which can be used to conveniently build a html menu.

Currently only available for use together with FuelPHP.

### Installing in FuelPHP 1.x

Just copy the navbuilder.php class file to the classes directory inside your app path.

> **Note**: Currently there is no project specific namespacing, waiting for FuelPHP to go fully composer compatible and then I'll rewrite this class as composer package.

### Usage examples

Build out menu structure, the syntax makes it easy to see the levels.

```php
$menu = NavBuilder::factory()
            ->add('About Us', 'about-us', NavBuilder::factory()
                ->add('Who We Are', 'who-we-are', null, false) // Conditionally exclude this item from the menu
                ->add('What We Do', 'what-we-do')
            ->add('Random', 'random', NavBuilder::factory()
                ->add('Link One', 'link-one')
                ->add('Link Two', 'link-two', NavBuilder::factory()
                    ->add('Level Three', 'level-three'))));
```

Optionally, add some attributes to the main list.

```php
$menu->attrs = array
(
    'id'    => 'navigation',
    'class' => 'menu',
);

/*
You can also do the following instead
$menu->id = 'navigation';
$menu->class = 'menu';
*/
```

Optionally, tell it the current active item specifying active link's url slug.

```php
$menu->current = 'level-three';
```

You can also specify a prefix, which will be added to all generated link URL's.

```php
$menu->pre_url = 'en/pages';
```

Finally, echo the menu.

```php
echo $menu;
```

The above generates the following HTML:

```html
<ul id="navigation" class="menu">
    <li class="parent"><a href="en/pages/about-us">About Us</a>
        <ul>
            <li><a href="en/pages/who-we-are">Who We Are</a></li>
            <li><a href="en/pages/what-we-do">What We Do</a></li>
            <li><a href="en/pages/other-things">Other Things</a></li>
        </ul>
    </li>
    <li class="parent active"><a href="en/pages/random">Random</a>
        <ul>
            <li><a href="en/pages/link-one">Link One</a></li>
            <li class="parent active"><a href="en/pages/link-two">Link Two</a>
                <ul>
                    <li class="active current"><a href="en/pages/level-three">Level Three</a></li>
                </ul>
            </li>
        </ul>
    </li>
</ul>
```

### Other ways to generate menus

You can also echo the menu object directly, and then, if you need to specify some attributes, you can pass them directly to the render() method.

```php
$attrs = array('class' => 'my_menu');
$active = 'item_one';
$pre_url = 'en/pages';

echo NavBuilder::factory()
         ->add('Item One', 'item_one')
         ->add('Item Two', 'item_two')
         ->render($attrs, $active, $pre_url);
```

Or if you find it easier to create the array yourself, you can do that and just pass in the array to the class.

```php
$menu = array
(
    array
	(
		'title' => 'Item One',
		'url' => 'item_one',
	),
	array
	(
		'title' => 'Item Two',
		'url' => 'item_two',
		'children' => array
		(
			array
			(
				'title' => 'Item Three',
				'url' => 'item_three',
                'show' => false,
			),
			array
			(
				'title' => 'Item Four',
				'url' => 'item_four',
			)
		)
	)
);

$attrs = array('id' => 'menu');
$active = 'item_one';
$pre_url = 'en/pages';

echo NavBuilder::factory($menu)->render($attrs, $active, $pre_url);
```

### Database Example

Below is an example of using this class with a database that holds your menu structure.
For example, create a table structure similar to this:

```sql
categories

+-----------+--------------+------+-----+---------+----------------+
| Field     | Type         | Null | Key | Default | Extra          |
+-----------+--------------+------+-----+---------+----------------+
| id        | int(11)      | NO   | PRI | NULL    | auto_increment |
| parent_id | int(11)      | NO   |     | 0       |                |
| title     | varchar(255) | NO   |     | NULL    |                |
| url       | varchar(255) | NO   |     | NULL    |                |
| is_active | tinyint(1)   | NO   |     | 1       |                |
+-----------+--------------+------+-----+---------+----------------+
```

And then, in your controller or view, you could do this to render the menu:

```php
$menu = NavBuilder::factory()->build()->render();

echo $menu;
```

Or, pass custom settings to the build method.

```php

/*
These are the default values already defined in the build method, so you don't have to specify them, if your database table looks like the one above, but you can pass an array like this with custom values.
*/

$settings = array(
    'table_name'            => 'categories',
    'id_column_name'        => 'id',
    'parent_column_name'    => 'parent_id',
    'title_column_name'     => 'title',
    'url_column_name'       => 'url',
    'order_by_column_name'  => 'id',
    'order_by_direction'    => 'asc',
    'is_active_column'      => 'is_active', // Should be boolean
);

$menu = NavBuilder::factory()->build($settings)->render();

echo $menu;
```
