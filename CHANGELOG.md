# Changelog

## Unreleased

The vendor is now Kingletas: the package is `kingletas/module-section-policy`, the namespace
`Kingletas\SectionPolicy` and the module `Kingletas_SectionPolicy`, and every config
section, table, console command and queue name starts with `kingletas`
instead of `commerce`. Nothing about how the module behaves changed; an
existing install moves its `commerce_` config rows and tables to `kingletas_`.

Tooling only. `make test` and `make cs` read Magento and the tools from this
package's own `vendor/`, which `make install` fills, and stop with instructions
when it is missing rather than running whatever `phpcs` or `phpunit` is on the
PATH. Nothing about how the module behaves changed.

## 1.0.0

First release.

Reports every action that invalidates all of customer data, measures what each
section costs to produce, and narrows the four `customer/account/*` actions to
the sections an operator names. Sections that say who the shopper is are refused
rather than dropped, a name that is not a registered section fails the report
rather than being ignored, and a store that installs it and names nothing gets
the map it already had.
