<?php
/**
 * Plugin Name: 文章原创
 * Plugin URI:
 * Description: 给文章添加原创勾选，批量设置，列表/分类页自动显示原创角标
 * Version: 1.0
 * Author: 
 * Text Domain: original-badge
 */

// 后台添加原创 meta 框
add_action('add_meta_boxes', 'ob_add_original_meta_box');
function ob_add_original_meta_box() {
    add_meta_box(
        'ob_original_meta',
        '原创设置',
        'ob_render_original_meta',
        'post',
        'side',
        'default'
    );
}

function ob_render_original_meta($post) {
    wp_nonce_field('ob_original_nonce', 'ob_original_nonce');
    $is_original = get_post_meta($post->ID, '_ob_is_original', true);
    ?>
    <label>
        <input type="checkbox" name="ob_is_original" value="1" <?php checked($is_original, '1'); ?>>
        标记为原创文章
    </label>
    <?php
}

// 保存原创状态
add_action('save_post', 'ob_save_original_meta');
function ob_save_original_meta($post_id) {
    if (!isset($_POST['ob_original_nonce']) || !wp_verify_nonce($_POST['ob_original_nonce'], 'ob_original_nonce'))
        return;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
        return;
    if (!current_user_can('edit_post', $post_id))
        return;

    update_post_meta($post_id, '_ob_is_original', isset($_POST['ob_is_original']) ? '1' : '0');
}

// 文章列表添加原创状态列
add_filter('manage_posts_columns', 'ob_add_original_column');
function ob_add_original_column($columns) {
    $columns['ob_original'] = '原创';
    return $columns;
}

add_action('manage_posts_custom_column', 'ob_show_original_column', 10, 2);
function ob_show_original_column($column, $post_id) {
    if ($column === 'ob_original') {
        $val = get_post_meta($post_id, '_ob_is_original', true);
        echo $val === '1' ? '<span style="color:green">✔ 是</span>' : '<span style="color:gray">否</span>';
    }
}

// 快速编辑
add_action('quick_edit_custom_box', 'ob_add_quick_edit', 10, 2);
function ob_add_quick_edit($column_name, $post_type) {
    if ($column_name !== 'ob_original' || $post_type !== 'post') return;
    wp_nonce_field('ob_original_nonce', 'ob_original_nonce');
    ?>
    <fieldset class="inline-edit-col-right inline-edit-<?php echo $column_name; ?>">
        <div class="inline-edit-col">
            <label class="inline-edit-group">
                <input type="checkbox" name="ob_is_original" value="1">
                <span class="checkbox-label">设为原创</span>
            </label>
        </div>
    </fieldset>
    <?php
}

// 保存快速编辑
add_action('save_post', 'ob_save_quick_edit');
function ob_save_quick_edit($post_id) {
    if (!isset($_POST['ob_original_nonce']) || !wp_verify_nonce($_POST['ob_original_nonce'], 'ob_original_nonce'))
        return;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
        return;
    if (!current_user_can('edit_post', $post_id))
        return;

    update_post_meta($post_id, '_ob_is_original', isset($_POST['ob_is_original']) ? '1' : '0');
}

// 批量编辑
add_action('bulk_edit_custom_box', 'ob_bulk_edit_box', 10, 2);
function ob_bulk_edit_box($column_name, $post_type) {
    if ($column_name !== 'ob_original' || $post_type !== 'post') return;
    ?>
    <fieldset class="inline-edit-col-right">
        <div class="inline-edit-col">
            <label>
                <select name="ob_bulk_original" style="width:100%">
                    <option value="">— 不修改 —</option>
                    <option value="1">设为原创</option>
                    <option value="0">取消原创</option>
                </select>
                <span>原创状态</span>
            </label>
        </div>
    </fieldset>
    <?php
}

// 保存批量编辑
add_action('wp_ajax_bulk_edit_original', 'ob_bulk_save_original');
add_action('load-edit.php', 'ob_bulk_save_original');
function ob_bulk_save_original() {
    if (!isset($_REQUEST['ob_bulk_original']) || $_REQUEST['ob_bulk_original'] === '')
        return;

    $wp_list_table = _get_list_table('WP_Posts_List_Table');
    $action = $wp_list_table->current_action();
    if ($action !== 'edit') return;

    check_admin_referer('bulk-posts');
    $post_ids = array_map('absint', $_REQUEST['post']);
    $val = sanitize_text_field($_REQUEST['ob_bulk_original']);

    foreach ($post_ids as $post_id) {
        if (!current_user_can('edit_post', $post_id)) continue;
        update_post_meta($post_id, '_ob_is_original', $val);
    }
}

// 给原创文章加 class
add_filter('post_class', 'ob_add_original_post_class');
function ob_add_original_post_class($classes) {
    if (is_single()) return $classes;
    $is_original = get_post_meta(get_the_ID(), '_ob_is_original', true);
    if ($is_original === '1') {
        $classes[] = 'ob-original-post';
    }
    return $classes;
}

// 输出角标 CSS
add_action('wp_head', 'ob_print_badge_css');
function ob_print_badge_css() {
    if (is_single()) return;
    ?>
    <style>
    .ob-original-post .ct-image-container::after {
        content: "";
        position: absolute;
        top: 0;
        right: 0;
        width: 60px;
        height: 60px;
        background-image: url("https://cdn.8i5.net/2026/04/Original.svg");
        background-repeat: no-repeat;
        background-size: contain;
        background-position: top right;
        pointer-events: none;
        z-index:1;
    }
    </style>
    <?php
}