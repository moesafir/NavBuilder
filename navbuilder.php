<?php
/**
 * NavBuilder
 *
 * A simple utility class for FuelPHP, which can be used to conveniently build a html menu
 * either form an array, a database table or manually, using add() method.
 *
 * @author   KrisOzolins <http://krisozolins.com>
 * @version  1.0
 */

class NavBuilder
{
    // Associative array of attributes for list
    public $attrs = array();

    // Current active URL
    public $current;

    // Optional string to add before each $url destination
    public $pre_url;

    // Associative array of links
    private $links = array();

    /**
     * Creates and returns a new NavBuilder object
     *
     * @param   array   Array of list links (instead of using add() method)
     * @return  NavBuilder
     * @chainable
     */
    public static function factory(array $links = array())
    {
        return new NavBuilder($links);
    }

    /**
     * Constructor, globally sets $links array
     *
     * @param   array   Array of list links (instead of using add() method)
     * @return  void
     */
    public function __construct(array $links = array())
    {
        $this->links = $links;
    }

    /**
     * Add's a new list item to the menu
     *
     * @chainable
     * @param   string  Title of the link
     * @param   string  URL (address) of the link, if null, uses $title name
     * @param   NavBuilder     Instance of the class that contains children
     * @param   bool    Conditionally set, whether to show this item or not
     * @return  NavBuilder
     */
    public function add($title, $url = null, NavBuilder $children = null, $show = true)
    {
        $this->links[] = array(
            'title'     => $title,
            'url'       => trim((!empty($url)) ? $url : Inflector::friendly_title($title, '-', true), '/'),
            'children'  => is_object($children) ? $children->links : null,
            'show'      => $show,
        );

        return $this;
    }

    /**
     * Builds the menu from a database table.
     *
     * @param   array   Associative array of database settings
     * @param   int     Parent ID, from which to build the menu
     * @return  NavBuilder
     */
    public function build(array $settings = array(), $parent_id = 0)
    {
        $default_settings = array(
            'table_name'            => 'categories',
            'id_column_name'        => 'id',
            'parent_column_name'    => 'parent_id',
            'title_column_name'     => 'title',
            'url_column_name'       => 'url',
            'order_by_column_name'  => 'id',
            'order_by_direction'    => 'asc',
            'is_active_column'      => 'is_active', // Should be boolean
        );

        $settings = array_merge($default_settings, $settings);

        $links = DB::select()->from($settings['table_name'])
            ->where($settings['parent_column_name'], '=', $parent_id)
            ->order_by($settings['order_by_column_name'], $settings['order_by_direction'])
            ->execute()->as_array();

        if (!empty($links)) {
            $nav = new NavBuilder;

            foreach ($links as $link) {
                if (!isset($link[$settings['is_active_column']])) {
                    $show = true;
                } else {
                    $show = $link[$settings['is_active_column']];
                }

                $nav->add($link[$settings['title_column_name']], $link[$settings['url_column_name']], $this->build($settings, $link[$settings['id_column_name']]), $show);
            }

            return $nav;
        }
    }

    /**
     * Renders the HTML output for the menu
     *
     * @param   array   Associative array of html attributes
     * @param   array   Associative array containing the key and value of current url
     * @param   array   The parent item's array, only used internally
     * @return  string  HTML unordered list
     */
    public function render(array $attrs = null, $current = null, array $links = null)
    {
        static $i;

        $links = empty($links) ? $this->links : $links;
        $current = empty($current) ? $this->current : $current;
        $attrs = empty($attrs) ? $this->attrs : $attrs;
        $pre_url = trim($this->pre_url, '/');

        $i++;

        $menu = '<ul'.($i == 1 ? self::attributes($attrs) : null).'>';

        foreach ($links as $key => $item) {
            $has_children = isset($item['children']);

            $class = array();

            $has_children ? $class[] = 'parent' : null;

            if ( ! empty($current)) {
                if ($current_class = self::current($current, $item)) {
                    $class[] = $current_class;
                }
            }

            $classes = ! empty($class) ? self::attributes(array('class' => implode(' ', $class))) : null;

            if (!isset($item['show']) || $item['show']) {
                $menu .= '<li'.$classes.'><a href="'.Uri::create($pre_url.'/'.$item['url']).'">'.$item['title'].'</a>';
                $menu .= $has_children ? $this->render(null, $current, $item['children']) : null;
                $menu .= '</li>';
            }
        }

        $menu .= '</ul>';

        $i--;

        return $menu;
    }

    /**
     * Renders the HTML output for menu without any attributes or active item
     *
     * @return   string
     */
    public function __toString()
    {
        return $this->render();
    }

    /**
     * Easily set the current url, or list attributes
     *
     * @param   mixed   Value to set to
     * @return  void
     */
    public function __set($key, $value)
    {
        $this->attrs[$key] = $value;
    }

    /**
     * Get the current url or a list attribute
     *
     * @return   mixed   Value of key
     */
    public function __get($key)
    {
        if (isset($this->attrs[$key])) {
            return $this->attrs[$key];
        }
    }

    /**
     * Compiles an array of HTML attributes into an attribute string.
     *
     * @param   string|array  array of attributes
     * @return  string
     */
    protected static function attributes($attrs)
    {
        if (empty($attrs)) {
            return '';
        }

        if (is_string($attrs)) {
            return ' '.$attrs;
        }

        $compiled = '';
        foreach ($attrs as $key => $val) {
            $compiled .= ' '.$key.'="'.htmlspecialchars($val).'"';
        }

        return $compiled;
    }

    /**
     * Figures out if links are parents of the active item.
     *
     * @param   array   The current url array (key, match)
     * @param   array   The array to check against
     * @return  bool
     */
    protected static function current($current, array $item)
    {
        if ($current === $item['url']) {
            return 'active current';
        } else {
            if (self::active($item, $current, 'url')) {
                return 'active';
            }
        }

        return '';
    }

    /**
     * Recursive function to check if active item is child of parent item
     *
     * @param   array   The list item
     * @param   string  The current active item
     * @param   string  Key to match current against
     * @return  bool
     */
    public static function active($array, $value, $key)
    {
        foreach ($array as $val) {
            if (is_array($val)) {
                if (self::active($val, $value, $key)) {
                    return true;
                }
            } else {
                if ($array[$key] === $value) {
                    return true;
                }
            }
        }

        return false;
    }
}
