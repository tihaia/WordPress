<?php get_header(); ?>

<div class="container site-content">
  <main class="content-area">
    <h2>Архив записей</h2>

    <?php if (have_posts()) : while (have_posts()) : the_post(); ?>
      <article class="post-item">
        <h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
        <p><?php the_excerpt(); ?></p>
      </article>
    <?php endwhile; else : ?>
      <p>Записей в архиве нет.</p>
    <?php endif; ?>
  </main>

  <?php get_sidebar(); ?>
</div>

<?php get_footer(); ?>