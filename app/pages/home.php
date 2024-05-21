<?php

use App\libs\BlogIndexLoader;
use App\libs\Partial;

$posts = BlogIndexLoader::load();

?>

<?php Partial::load('header') ?>

<section>
    <?php foreach ($posts as $post): ?>
        <article class="w-full border-solid border border-gray-200 rounded-lg mt-8 p-4">
            <h2 class="mt-0 mb-0 text-3xl">
                <a href="/posts/<?= $post['slug']?>"
                   class="no-underline text-gray-800 text-2xl hover:underline"><?= $post['title'] ?>
                </a>
            </h2>
            <span class="text-gray-500 text-xs">Published: <?= $post['published_at'] ?></span>
            <span class="text-gray-500 text-xs">Author:' <?= $post['author'] ?></span>
            <p class="mt-2 p-0 leading-relaxed"><?= $post['summary'] ?></p>
        </article>
    <?php endforeach; ?>

</section>

<?php Partial::load('footer') ?>