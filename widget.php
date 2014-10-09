<?php
/**
 * Project : Advanced-Recent-Post-Widget
 * User: thuytien
 * Date: 10/9/2014
 * Time: 11:29 AM
 */
class MTR_Widget_Recent_Posts_Advance extends WP_Widget
{

    function __construct()
    {
        $widget_ops = array('classname' => 'widget_recent_entries_advance', 'description' => __("Your site&#8217;s most recent Posts with advanced options."));
        parent::__construct('recent-posts-advance', __('Advanced Recent Posts'), $widget_ops);

        add_action('save_post', array($this, 'flush_widget_cache'));
        add_action('deleted_post', array($this, 'flush_widget_cache'));
        add_action('switch_theme', array($this, 'flush_widget_cache'));
    }

    function widget($args, $instance)
    {
        $condition = (!empty($instance['condittion'])) ? $instance['condittion'] : '';
        if (eval("return !( " . $condition . ");")) {
            return;
        }
        $cache = array();
        if (!$this->is_preview()) {
            $cache = wp_cache_get('widget_recent_posts_advance', 'widget');
        }

        if (!is_array($cache)) {
            $cache = array();
        }

        if (!isset($args['widget_id'])) {
            $args['widget_id'] = $this->id;
        }

        if (isset($cache[$args['widget_id']])) {
            echo $cache[$args['widget_id']];
            return;
        }

        ob_start();
        extract($args);

        $title = (!empty($instance['title'])) ? $instance['title'] : __('Recent Posts');

        /** This filter is documented in wp-includes/default-widgets.php */
        $title = apply_filters('widget_title', $title, $instance, $this->id_base);

        $number = (!empty($instance['number'])) ? absint($instance['number']) : 5;
        if (!$number) {
            $number = 5;
        }
        $show_date = isset($instance['show_date']) ? $instance['show_date'] : false;

        $query_extra = array();

        if (!(is_home() || is_front_page())) {
            if ($instance['filter_by_categories']) {
                $categories = get_the_category();
                if ($categories) {
                    $query_extra['category__in'] = array();
                    foreach ($categories as $category) {
                        $query_extra['category__in'][] = $category->term_id;
                    }
                }
            }

            if ($instance['filter_by_tags']) {
                $tags = get_the_tags();
                if ($tags) {
                    $query_extra['tag__in'] = array();
                    foreach ($tags as $tag) {
                        $query_extra['tag__in'][] = $tag->term_id;
                    }
                }
            }
        }

        $query_args = apply_filters('widget_posts_advance_args', array(
            'posts_per_page' => $number,
            'no_found_rows' => true,
            'post_status' => 'publish',
            'ignore_sticky_posts' => true
        ));

        $query_args = array_merge($query_args, $query_extra);

        $r = new WP_Query($query_args);

        if ($r->have_posts()) :
            ?>
            <?php echo $before_widget; ?>
            <?php if ($title) echo $before_title . $title . $after_title; ?>
            <ul>
                <?php while ($r->have_posts()) : $r->the_post(); ?>
                    <li>
                        <a href="<?php the_permalink(); ?>"><?php get_the_title() ? the_title() : the_ID(); ?></a>
                        <?php if ($show_date) : ?>
                            <span class="post-date"><?php echo get_the_date(); ?></span>
                        <?php endif; ?>
                    </li>
                <?php endwhile; ?>
            </ul>
            <?php echo $after_widget; ?>
            <?php
            // Reset the global $the_post as this query will have stomped on it
            wp_reset_postdata();

        endif;

        if (!$this->is_preview()) {
            $cache[$args['widget_id']] = ob_get_flush();
            wp_cache_set('widget_recent_posts_advance', $cache, 'widget');
        } else {
            ob_end_flush();
        }
    }

    function update($new_instance, $old_instance)
    {
        $instance = $old_instance;
        $instance['title'] = strip_tags($new_instance['title']);
        $instance['number'] = (int)$new_instance['number'];
        $instance['show_date'] = isset($new_instance['show_date']) ? (bool)$new_instance['show_date'] : false;
        $instance['condittion'] = strip_tags($new_instance['condittion']);
        $instance['filter_by_categories'] = isset($new_instance['filter_by_categories']) ? (bool)$new_instance['filter_by_categories'] : false;
        $instance['filter_by_tags'] = isset($new_instance['filter_by_tags']) ? (bool)$new_instance['filter_by_tags'] : false;
        $this->flush_widget_cache();

        return $instance;
    }

    function flush_widget_cache()
    {
        wp_cache_delete('widget_recent_posts_advance', 'widget');
    }

    function form($instance)
    {
        $title = isset($instance['title']) ? esc_attr($instance['title']) : '';
        $number = isset($instance['number']) ? absint($instance['number']) : 5;
        $show_date = isset($instance['show_date']) ? (bool)$instance['show_date'] : false;
        $condittion = isset($instance['condittion']) ? esc_attr($instance['condittion']) : '';
        $filter_by_categories = isset($instance['filter_by_categories']) ? $instance['filter_by_categories'] : false;
        $filter_by_tags = isset($instance['filter_by_tags']) ? $instance['filter_by_tags'] : false;

        ?>
        <p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:'); ?></label>
            <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>"
                   name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>"/></p>

        <p><label for="<?php echo $this->get_field_id('number'); ?>"><?php _e('Number of posts to show:'); ?></label>
            <input id="<?php echo $this->get_field_id('number'); ?>"
                   name="<?php echo $this->get_field_name('number'); ?>" type="text" value="<?php echo $number; ?>"
                   size="3"/></p>

        <p><input class="checkbox" type="checkbox" <?php checked($show_date); ?>
                  id="<?php echo $this->get_field_id('show_date'); ?>"
                  name="<?php echo $this->get_field_name('show_date'); ?>"/>
            <label for="<?php echo $this->get_field_id('show_date'); ?>"><?php _e('Display post date ?'); ?></label></p>

        <p><input class="checkbox" type="checkbox" <?php checked($filter_by_categories); ?>
                  id="<?php echo $this->get_field_id('filter_by_categories'); ?>"
                  name="<?php echo $this->get_field_name('filter_by_categories'); ?>"/>
            <label
                for="<?php echo $this->get_field_id('filter_by_categories'); ?>"><?php _e('Filter by current category ?'); ?></label>
        </p>

        <p><input class="checkbox" type="checkbox" <?php checked($filter_by_tags); ?>
                  id="<?php echo $this->get_field_id('filter_by_tags'); ?>"
                  name="<?php echo $this->get_field_name('filter_by_tags'); ?>"/>
            <label
                for="<?php echo $this->get_field_id('filter_by_tags'); ?>"><?php _e('Filter by current tags ?'); ?></label>
        </p>

        <p><label for="<?php echo $this->get_field_id('condittion'); ?>"><?php _e('Condittion:'); ?></label>
            <textarea class="widefat" id="<?php echo $this->get_field_id('condittion'); ?>"
                      name="<?php echo $this->get_field_name('condittion'); ?>"><?php echo $condittion; ?></textarea>
        </p>
    <?php
    }
}