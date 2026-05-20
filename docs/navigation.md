# Navigation

Navigation is generated from loaded `DocumentPage` objects.

```php
$navigation = $docs->navigation('guide/install');

echo $navigation->toHtml();
```

## Ordering

Pages sort by:

1. `order` metadata;
2. navigation title;
3. document path.

```markdown
---
title: Install
navTitle: Setup
order: 10
---
# Install
```

## Hidden Pages

Pages with truthy `hidden` metadata are available through the loader, but omitted from generated navigation.

Truthy values include:

- `true`
- `1`
- `yes`
- `on`

## Synthetic Parents

If a child page exists without a parent page, `NavigationBuilder` creates a synthetic parent item.

For this page:

```text
guide/install.md
```

navigation includes a `Guide` parent even when `guide.md` or `guide/index.md` is absent.

## Navigation Objects

`Navigation` supports:

- `items()`
- `all()`
- `find()`
- `flatten()`
- `toArray()`
- `toHtml()`
- `Countable`
- `IteratorAggregate`

`NavigationItem` exposes:

- `path()`
- `title()`
- `href()`
- `isActive()`
- `isCurrent()`
- `children()`
- `withChildren()`
- `toArray()`
