<?php
/**
 * Emulate the WordPress template hierarchy with twig files.
 * This allows you to skip creating php files for each page. Instead, just create the twig file and begin working.
 * PHP files should only be created when it is necessary to add some special logic to the $context that is unique to
 * that page. Otherwise you can rely on the hierarchy to decide which twig file to render.
 *
 * You are some filters that will allow you to change/adapt the hierarchy to your needs.
 *
 * @filter {$type}_twig_template_hierarchy Will filter the specified type of templates when they are considered in the
 * render process.
 * @param string $type one of 'index', '404', 'archive', 'author', 'category', 'tag', 'taxonomy', 'date', 'embed',
 *        'home', 'frontpage', 'page', 'paged', 'search', 'single', 'singular', and 'attachment'.
 *
 * @filter twig_template_hierarchy Will always run over all templates that are passed to the Timber::render method.
 */


namespace Mindfullsilence;

class Hierarchy {
    private static $extension = 'php';

    protected function __construct() {}

    public static function set_extension($extension) {
      $extension = ltrim($extension, '.');
      self::$extension = $extension;
    }

    public static function get_file($name) {
      return implode('.', array($name, self::$extension));
    }

    /**
     * Runs through the wordpress hierarchy to get the array of templates that should be considered for render
     * @return array The appropriate twig files to render to.
     */
    public static function get_hierarchy() {
        $template = array();
        if ( is_embed() ) {
            $template = array_merge($template, self::get_embed_template());
        }
        if ( is_404() ) {
            $template = array_merge($template, self::get_404_template());
        }
        if ( is_search() ) {
            $template = array_merge($template, self::get_search_template());
        }
        if ( is_front_page() ) {
            $template = array_merge($template, self::get_front_page_template());
        }
        if ( is_home() ) {
            $template = array_merge($template, self::get_home_template());
        }
        if ( is_post_type_archive() ) {
            $template = array_merge($template, self::get_post_type_archive_template());
        }
        if ( is_tax() ) {
            $template = array_merge($template, self::get_taxonomy_template());
        }
        if ( is_attachment() ) {
            $template = array_merge($template, self::get_attachment_template());
            remove_filter('the_content', 'prepend_attachment');
        }
        if ( is_single() ) {
            $template = array_merge($template, self::get_single_template());
        }
        if ( is_page() ) {
            $template = array_merge($template, self::get_page_template());
        }
        if ( is_singular() ) {
            $template = array_merge($template, self::get_singular_template());
        }
        if ( is_category() ) {
            $template = array_merge($template, self::get_category_template());
        }
        if ( is_tag() ) {
            $template = array_merge($template, self::get_tag_template());
        }
        if ( is_author() ) {
            $template = array_merge($template, self::get_author_template());
        }
        if ( is_date() ) {
            $template = array_merge($template, self::get_date_template());
        }
        if ( is_archive() ) {
            $template = array_merge($template, self::get_archive_template());
        }

        $template = array_merge($template, self::get_index_template()); // index fallback

        return self::run_filter($template);
    }

    /**
     * @param array $templates Templates to pass through the filter over
     * @param string $type The type of filter. Can be one of 'index', '404', 'archive', 'author', 'category', 'tag',
     *        'taxonomy', 'date', 'embed', 'home', 'frontpage', 'page', 'paged', 'search', 'single', 'singular', and
     *        'attachment'.
     * @return array the array of templates after filters have been applied.
     */
    public static function run_filter($templates = array(), $type = '') {
        if('' != trim($type)) {
            return apply_filters("{$type}_template_hierarchy", $templates);
        }

        return apply_filters( 'template_hierarchy', $templates );
    }

    /**
     * @return array index.twig
     */
    public static function get_index_template() {
      $templates = array(self::get_file('index'));
      return self::run_filter($templates, 'index');
    }

    /**
     * @return array 404.twig
     */
    public static function get_404_template() {
        $templates = array(self::get_file('404'));
        return self::run_filter($templates, '404');
    }

    /**
     * @return array [archive-{post_type}.twig, archive.twig]
     */
    public static function get_archive_template() {
        $post_types = array_filter( (array) get_query_var( 'post_type' ) );
        $templates = array();
        if ( count( $post_types ) == 1 ) {
            $post_type = reset( $post_types );
            $templates[] = self::get_file("archive-{$post_type}");
        }
        $templates[] = self::get_file('archive.twig');
        return self::run_filter($templates, 'archive');
    }

    /**
     * @return array [archive-{post_type}.twig, archive.twig]
     */
    public static function get_post_type_archive_template() {
        $post_type = get_query_var( 'post_type' );
        if ( is_array( $post_type ) ) {
            $post_type = reset( $post_type );
        }
        $obj = get_post_type_object( $post_type );
        if ( ! $obj->has_archive ) {
            return array('');
        }
        return self::get_archive_template();
    }

    /**
     * @return array [author-{user_nicename}.twig, author-{ID}.twig, author.twig]
     */
    public static function get_author_template() {
        $author = get_queried_object();
        $templates = array();
        if ( $author instanceof WP_User ) {
            $templates[] = self::get_file("author-{$author->user_nicename}");
            $templates[] = self::get_file("author-{$author->ID}");
        }
        $templates[] = self::get_file('author');
        return self::run_filter($templates, 'author');
    }

    /**
     * @return array [category-{slug}.twig, category-{ID}.twig, category.twig]
     */
    public static function get_category_template() {
        $category = get_queried_object();
        $templates = array();
        if ( ! empty( $category->slug ) ) {
            $slug_decoded = urldecode( $category->slug );
            if ( $slug_decoded !== $category->slug ) {
                $templates[] = self::get_file("category-{$slug_decoded}");
            }
            $templates[] = self::get_file("category-{$category->slug}");
            $templates[] = self::get_file("category-{$category->term_id}");
        }
        $templates[] = self::get_file('category');
        return self::run_filter($templates, 'category');
    }

    /**
     * @return array [tag-{slug}.twig, tag-{ID}.twig, tag.twig]
     */
    public static function get_tag_template() {
        $tag = get_queried_object();
        $templates = array();
        if ( ! empty( $tag->slug ) ) {
            $slug_decoded = urldecode( $tag->slug );
            if ( $slug_decoded !== $tag->slug ) {
                $templates[] = self::get_file("tag-{$slug_decoded}");
            }
            $templates[] = self::get_file("tag-{$tag->slug}");
            $templates[] = self::get_file("tag-{$tag->term_id}");
        }
        $templates[] = self::get_file('tag');
        return self::run_filter($templates, 'tag');
    }

    /**
     * @return array [taxonomy-{slug}.twig, taxonomy-{ID}.twig, taxonomy.twig]
     */
    public static function get_taxonomy_template() {
        $term = get_queried_object();
        $templates = array();
        if ( ! empty( $term->slug ) ) {
            $taxonomy = $term->taxonomy;
            $slug_decoded = urldecode( $term->slug );
            if ( $slug_decoded !== $term->slug ) {
                $templates[] = self::get_file("taxonomy-$taxonomy-{$slug_decoded}");
            }
            $templates[] = self::get_file("taxonomy-$taxonomy-{$term->slug}");
            $templates[] = self::get_file("taxonomy-$taxonomy");
        }
        $templates[] = self::get_file('taxonomy');
        return self::run_filter($templates, 'taxonomy');
    }

    /**
     * @return array [date.twig]
     */
    public static function get_date_template() {
      $templates = array();
      $templates[] = self::get_file('date');
      return self::run_filter($templates, 'date');
    }

    /**
     * @return array [home.twig, index.twig]
     */
    public static function get_home_template() {
        $templates = array();
        $templates[] = self::get_file('home');
        $templates[] = self::get_file('index');
        return self::run_filter($templates, 'home');
    }

    /**
     * @return array [front-page.twig]
     */
    public static function get_front_page_template() {
      $templates = array();
      $templates[] = self::get_file('front-page');
        return self::run_filter($templates, 'front_page');
    }

    /**
     * @return array [{custom-page-template}.twig, page-{slug}.twig, page-{ID}.twig, page.twig]
     */
    public static function get_page_template() {
        $templates = array();
        $id = get_queried_object_id();
        $template = self::get_file(rtrim(get_page_template_slug(), '.php'));
        $pagename = get_query_var('pagename');
        if ( ! $pagename && $id ) {
            $post = get_queried_object();
            if ( $post )
                $pagename = $post->post_name;
        }
        if ( $template )
            $templates[] = $template;
        if ( $pagename ) {
            $pagename_decoded = urldecode( $pagename );
            if ( $pagename_decoded !== $pagename ) {
                $templates[] = self::get_file("page-{$pagename_decoded}");
            }
            $templates[] = self::get_file("page-$pagename");
        }
        if ( $id )
            $templates[] = self::get_file("page-$id");
        $templates[] = self::get_file('page');
        return self::run_filter($templates, 'page');
    }

    /**
     * @return array [search.twig]
     */
    public static function get_search_template() {
      $templates = array();
        $templates[] = self::get_file('search');
        return self::run_filter($templates, 'search');
    }

    /**
     * @return array [single-{post_type}-{slug}.twig, single-{post_type}-{ID}.twig, single-{post_type}.twig, single.twig]
     */
    public static function get_single_template() {
        $object = get_queried_object();
        $templates = array();
        if ( ! empty( $object->post_type ) ) {
            $template = get_page_template_slug( $object );
            if ( $template && 0 === validate_file( $template ) ) {
                $templates[] = $template;
            }
            $name_decoded = urldecode( $object->post_name );
            if ( $name_decoded !== $object->post_name ) {
                $templates[] = self::get_file("single-{$object->post_type}-{$name_decoded}");
            }
            $templates[] = self::get_file("single-{$object->post_type}-{$object->post_name}");
            $templates[] = self::get_file("single-{$object->post_type}");
        }
        $templates[] = self::get_file("single.twig");
        return self::run_filter($templates, 'single');
    }

    /**
     * @return array [embed-{post_type}-{post_format}.twig, embed-{post_type}.twig, embed.twig]
     */
    public static function get_embed_template() {
        $object = get_queried_object();
        $templates = array();
        if ( ! empty( $object->post_type ) ) {
            $post_format = get_post_format( $object );
            if ( $post_format ) {
                $templates[] = self::get_file("embed-{$object->post_type}-{$post_format}");
            }
            $templates[] = self::get_file("embed-{$object->post_type}");
        }
        $templates[] = self::get_file("embed");
        return self::run_filter($templates, 'embed');
    }

    /**
     * @return array [singular.twig]
     */
    public static function get_singular_template() {
      $templates = array();
        $templates = array(self::get_file('singular'));
        return self::run_filter($templates, 'singular');
    }

    /**
     * @return array [{mimetype}-{subtype}.twig, {subtype}.twig, {mimetype}.twig, attachment.twig]
     */
    public static function get_attachment_template() {
        $attachment = get_queried_object();
        $templates = array();
        if ( $attachment ) {
            if ( false !== strpos( $attachment->post_mime_type, '/' ) ) {
                list( $type, $subtype ) = explode( '/', $attachment->post_mime_type );
            } else {
                list( $type, $subtype ) = array( $attachment->post_mime_type, '' );
            }
            if ( ! empty( $subtype ) ) {
                $templates[] = self::get_file("{$type}-{$subtype}");
                $templates[] = self::get_file("{$subtype}");
            }
            $templates[] = self::get_file("{$type}");
        }
        $templates[] = self::get_file('attachment');
        return self::run_filter($templates, 'attachment');
    }


}
