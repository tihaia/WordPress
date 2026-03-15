<?php get_header(); ?>

<div class="container site-content">
  <main class="content-area">
    <?php if (have_posts()) : while (have_posts()) : the_post(); ?>
      <article class="post-item">
        <h2><?php the_title(); ?></h2>
        <div><?php the_content(); ?></div>
      </article>

      <?php comments_template(); ?>
    <?php endwhile; endif; ?>
  </main>

  <?php get_sidebar(); ?>
</div>

<?php get_footer(); ?>