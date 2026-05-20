<?php

declare(strict_types=1);

namespace CommonPHP\Docs;

use CommonPHP\Docs\Contracts\DocumentationRendererInterface;
use CommonPHP\Docs\Contracts\MarkdownConverterInterface;
use CommonPHP\Docs\Contracts\NavigationBuilderInterface;
use CommonPHP\Runtime\Contracts\ServiceProviderInterface;
use DI\ContainerBuilder;

use function DI\autowire;
use function DI\factory;

final class DocsServiceProvider implements ServiceProviderInterface
{
    public function configure(ContainerBuilder $builder): void
    {
        $builder->addDefinitions([
            MarkdownConverterInterface::class => autowire(MarkdownConverter::class),
            DocumentationRendererInterface::class => autowire(DocumentationRenderer::class),
            NavigationBuilderInterface::class => autowire(NavigationBuilder::class),
            DocumentationRegistry::class => autowire(DocumentationRegistry::class),
            DocSurface::class => factory(static fn (DocumentationRegistry $registry): DocSurface => new DocSurface($registry)),
        ]);
    }
}
