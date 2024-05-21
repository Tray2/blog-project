<?php

use App\libs\Partial;
use App\libs\PostLoader;

$loader = new PostLoader(explode('/', trim($_SERVER['REQUEST_URI'], '/'))[1]);

$imagePath = '';

if (isset($loader->getFrontMatter()['image'])) {
    $imagePath = __DIR__ . '/../../images/posts/' . $loader->getFrontMatter()['image'];
}

Partial::load('header');
?>

<section class="mt-5 prose">

    <?php if (file_exists($imagePath)) : ?>
        <img src="/images/posts/<?= $loader->getFrontMatter()['image'] ?>" alt="<?= $loader->getFrontMatter()['title'] ?>">
    <?php else : ?>
        <h1 class="text-3xl mb-2"><?= $loader->getFrontMatter()['title'] ?></h1>
    <?php endif; ?>

    <article class="line-numbers"><?= $loader->getPost() ?></article>

</section>

<?php Partial::load('footer'); ?>