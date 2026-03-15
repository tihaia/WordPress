<div class="comments-section">
  <h3>Комментарии</h3>

  <?php if (have_comments()) : ?>
    <ul>
      <?php wp_list_comments(); ?>
    </ul>
  <?php endif; ?>

  <?php comment_form(); ?>
</div>