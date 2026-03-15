<?php get_header(); ?>

<div class="container site-content">
  <main class="content-area">
    <h2>Последние записи</h2>

    <?php if (have_posts()) : ?>
      <?php $count = 0; ?>
      <?php while (have_posts() && $count < 5) : the_post(); ?>
        <article class="post-item">
          <h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
          <p><?php the_excerpt(); ?></p>
        </article>
        <?php $count++; ?>
      <?php endwhile; ?>
    <?php else : ?>
      <p>Записей пока нет.</p>
    <?php endif; ?>
  </main>

  <?php get_sidebar(); ?>
</div>

<?php get_footer(); ?>