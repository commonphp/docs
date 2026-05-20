<?php

declare(strict_types=1);

namespace CommonPHP\Docs\Tests\Unit;

use CommonPHP\Docs\Contracts\DocumentationRendererInterface;
use CommonPHP\Docs\Contracts\MarkdownConverterInterface;
use CommonPHP\Docs\Contracts\NavigationBuilderInterface;
use CommonPHP\Docs\DocSurface;
use CommonPHP\Docs\DocsServiceProvider;
use CommonPHP\Docs\DocumentationRegistry;
use CommonPHP\Docs\MarkdownConverter;
use CommonPHP\Docs\NavigationBuilder;
use CommonPHP\Runtime\Contracts\ServiceProviderInterface;
use DI\ContainerBuilder;
use PHPUnit\Framework\TestCase;

final class DocsServiceProviderTest extends TestCase
{
    public function testItRegistersDocumentationServices(): void
    {
        $provider = new DocsServiceProvider();
        $builder = new ContainerBuilder();
        $provider->configure($builder);
        $container = $builder->build();

        self::assertInstanceOf(ServiceProviderInterface::class, $provider);
        self::assertInstanceOf(MarkdownConverter::class, $container->get(MarkdownConverterInterface::class));
        self::assertInstanceOf(DocumentationRendererInterface::class, $container->get(DocumentationRendererInterface::class));
        self::assertInstanceOf(NavigationBuilder::class, $container->get(NavigationBuilderInterface::class));
        self::assertInstanceOf(DocumentationRegistry::class, $container->get(DocumentationRegistry::class));
        self::assertInstanceOf(DocSurface::class, $container->get(DocSurface::class));
    }
}
