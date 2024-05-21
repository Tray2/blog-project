<?php

namespace App\libs;

class PostLoader
{
    protected string $post;
    protected array $frontMatter;
    public function __construct(string $post, string $path = __DIR__ . '/../../posts/')
    {
        $this->redirectIf404($post , $path);
        $parts = preg_split('/^---$/m' , $this->getPostContent($post, $path), -1, PREG_SPLIT_NO_EMPTY);
        $this->frontMatter = FrontMatter::parse($parts[0]);
        $this->post = MarkdownParser::parse($parts[1]);
    }

    public function getFrontMatter(): array
    {
        return $this->frontMatter;
    }

    public function getPost(): string
    {
        return $this->post;
    }

    protected function redirectIf404(string $post, string $path): void
    {
        if (!file_exists($path . $post . '.md')) {
            header("Location: /404", true, 302);
        }
    }

    protected function getPostContent(string $post, string $path): string|false
    {
        return file_get_contents($path . $post . '.md');
    }
}