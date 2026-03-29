<?php
/*
Plugin Name: USM Notes
Plugin URI: http://localhost/
Description: Учебный плагин для заметок с приоритетами и датой напоминания.
Version: 1.0
Author: Tanya
Author URI: http://localhost/
License: GPL2
*/

if (!defined('ABSPATH')) {
    exit;
}

function usm_register_notes_cpt() {
    $labels = array(
        'name'                  => 'Заметки',
        'singular_name'         => 'Заметка',
        'menu_name'             => 'Заметки',
        'name_admin_bar'        => 'Заметка',
        'add_new'               => 'Добавить новую',
        'add_new_item'          => 'Добавить новую заметку',
        'new_item'              => 'Новая заметка',
        'edit_item'             => 'Редактировать заметку',
        'view_item'             => 'Просмотреть заметку',
        'all_items'             => 'Все заметки',
        'search_items'          => 'Искать заметки',
        'not_found'             => 'Заметки не найдены',
        'not_found_in_trash'    => 'В корзине заметок нет'
    );

    $args = array(
        'labels'             => $labels,
        'public'             => true,
        'has_archive'        => true,
        'menu_icon'          => 'dashicons-edit-page',
        'supports'           => array('title', 'editor', 'author', 'thumbnail'),
        'show_in_rest'       => true,
        'rewrite'            => array('slug' => 'notes'),
    );

    register_post_type('usm_note', $args);
}

add_action('init', 'usm_register_notes_cpt');
function usm_register_priority_taxonomy() {
    $labels = array(
        'name'              => 'Приоритеты',
        'singular_name'     => 'Приоритет',
        'search_items'      => 'Искать приоритеты',
        'all_items'         => 'Все приоритеты',
        'parent_item'       => 'Родительский приоритет',
        'parent_item_colon' => 'Родительский приоритет:',
        'edit_item'         => 'Редактировать приоритет',
        'update_item'       => 'Обновить приоритет',
        'add_new_item'      => 'Добавить новый приоритет',
        'new_item_name'     => 'Название нового приоритета',
        'menu_name'         => 'Приоритет'
    );

    $args = array(
        'hierarchical'      => true,
        'labels'            => $labels,
        'public'            => true,
        'show_admin_column' => true,
        'show_in_rest'      => true,
        'rewrite'           => array('slug' => 'priority'),
    );

    register_taxonomy('priority', array('usm_note'), $args);
}

add_action('init', 'usm_register_priority_taxonomy');
/* =========================================================
   Шаг 5. Метабокс для даты напоминания
========================================================= */

/* 1. Добавление метабокса */
function usm_add_due_date_metabox() {
    add_meta_box(
        'usm_due_date_metabox',
        'Дата напоминания',
        'usm_due_date_metabox_callback',
        'usm_note',
        'side',
        'default'
    );
}
add_action('add_meta_boxes', 'usm_add_due_date_metabox');


/* 2. Поле даты в метабоксе */
function usm_due_date_metabox_callback($post) {
    wp_nonce_field('usm_save_due_date', 'usm_due_date_nonce');

    $value = get_post_meta($post->ID, '_usm_due_date', true);
    $today = current_time('Y-m-d');

    echo '<label for="usm_due_date_field"><strong>Выберите дату напоминания:</strong></label>';
    echo '<input type="date"
                 id="usm_due_date_field"
                 name="usm_due_date_field"
                 value="' . esc_attr($value) . '"
                 min="' . esc_attr($today) . '"
                 required
                 style="width:100%; margin-top:8px;">';

    echo '<p style="font-size:12px; color:#666; margin-top:8px;">
            Дата обязательна и не может быть в прошлом.
          </p>';
}


/* 3. Проверка даты перед сохранением */
function usm_validate_due_date_before_save($data, $postarr) {
    if (!is_admin()) {
        return $data;
    }

    if (!isset($data['post_type']) || $data['post_type'] !== 'usm_note') {
        return $data;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return $data;
    }

    if (!isset($_POST['usm_due_date_nonce'])) {
        return $data;
    }

    if (!wp_verify_nonce($_POST['usm_due_date_nonce'], 'usm_save_due_date')) {
        return $data;
    }

    $due_date = isset($_POST['usm_due_date_field']) ? sanitize_text_field($_POST['usm_due_date_field']) : '';
    $today    = current_time('Y-m-d');

    if (empty($due_date)) {
        set_transient('usm_due_date_error', 'Ошибка: поле даты обязательно для заполнения.', 30);
        $data['post_status'] = 'draft';
        return $data;
    }

    if ($due_date < $today) {
        set_transient('usm_due_date_error', 'Ошибка: дата не может быть в прошлом.', 30);
        $data['post_status'] = 'draft';
        return $data;
    }

    return $data;
}
add_filter('wp_insert_post_data', 'usm_validate_due_date_before_save', 10, 2);


function usm_save_due_date_meta($post_id) {
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    if (wp_is_post_revision($post_id)) {
        return;
    }

    if (get_post_type($post_id) !== 'usm_note') {
        return;
    }

    if (!isset($_POST['usm_due_date_nonce'])) {
        return;
    }

    if (!wp_verify_nonce($_POST['usm_due_date_nonce'], 'usm_save_due_date')) {
        return;
    }

    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    if (isset($_POST['usm_due_date_field'])) {
        $due_date = sanitize_text_field($_POST['usm_due_date_field']);
        update_post_meta($post_id, '_usm_due_date', $due_date);
    }
}
add_action('save_post', 'usm_save_due_date_meta', 10);

/* ======================================
   6. Колонка Due Date в списке заметок
====================================== */
function usm_add_due_date_column($columns) {
    $columns['usm_due_date'] = 'Due Date';
    return $columns;
}
add_filter('manage_usm_note_posts_columns', 'usm_add_due_date_column');

function usm_show_due_date_column($column, $post_id) {
    if ($column === 'usm_due_date') {
        $due_date = get_post_meta($post_id, '_usm_due_date', true);
        echo $due_date ? esc_html($due_date) : '—';
    }
}
add_action('manage_usm_note_posts_custom_column', 'usm_show_due_date_column', 10, 2);
function usm_notes_shortcode($atts) {
    $atts = shortcode_atts(array(
        'priority'    => '',
        'before_date' => '',
    ), $atts, 'usm_notes');

    $meta_query = array();
    $tax_query = array();

    if (!empty($atts['before_date'])) {
        $meta_query[] = array(
            'key'     => '_usm_due_date',
            'value'   => sanitize_text_field($atts['before_date']),
            'compare' => '<=',
            'type'    => 'DATE',
        );
    }

    if (!empty($atts['priority'])) {
        $tax_query[] = array(
            'taxonomy' => 'priority',
            'field'    => 'slug',
            'terms'    => sanitize_title($atts['priority']),
        );
    }

    $args = array(
        'post_type'      => 'usm_note',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'meta_key'       => '_usm_due_date',
        'orderby'        => 'meta_value',
        'order'          => 'ASC',
    );

    if (!empty($meta_query)) {
        $args['meta_query'] = $meta_query;
    }

    if (!empty($tax_query)) {
        $args['tax_query'] = $tax_query;
    }

    $query = new WP_Query($args);

    ob_start();

    if ($query->have_posts()) {
        echo '<div class="usm-notes-list">';

        while ($query->have_posts()) {
            $query->the_post();

            $due_date = get_post_meta(get_the_ID(), '_usm_due_date', true);
            $terms = get_the_terms(get_the_ID(), 'priority');

            echo '<div class="usm-note-item">';
            echo '<h3 class="usm-note-title">' . esc_html(get_the_title()) . '</h3>';
            echo '<div class="usm-note-content">' . wp_kses_post(get_the_content()) . '</div>';

            if (!empty($terms) && !is_wp_error($terms)) {
                echo '<p class="usm-note-priority"><strong>Priority:</strong> ' . esc_html($terms[0]->name) . '</p>';
            }

            if (!empty($due_date)) {
                echo '<p class="usm-note-date"><strong>Due Date:</strong> ' . esc_html($due_date) . '</p>';
            }

            echo '</div>';
        }

        echo '</div>';
    } else {
        echo '<p>Нет заметок с заданными параметрами</p>';
    }

    wp_reset_postdata();

    return ob_get_clean();
}
add_shortcode('usm_notes', 'usm_notes_shortcode');
function usm_notes_styles() {
    echo '
    <style>
        .usm-notes-list {
            display: flex;
            flex-direction: column;
            gap: 20px;
            margin: 20px 0;
        }

        .usm-note-item {
            border: 1px solid #ddd;
            border-left: 5px solid #0073aa;
            background: #f9f9f9;
            padding: 16px;
            border-radius: 8px;
        }

        .usm-note-title {
            margin: 0 0 10px;
            font-size: 22px;
        }

        .usm-note-content {
            margin-bottom: 10px;
        }

        .usm-note-priority,
        .usm-note-date {
            margin: 6px 0;
        }
    </style>
    ';
}
add_action('wp_head', 'usm_notes_styles');