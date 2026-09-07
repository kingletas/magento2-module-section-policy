# Changelog

## 1.0.0

First release.

Reports every action that invalidates all of customer data, measures what each
section costs to produce, and narrows the four `customer/account/*` actions to
the sections an operator names. Sections that say who the shopper is are refused
rather than dropped, a name that is not a registered section fails the report
rather than being ignored, and a store that installs it and names nothing gets
the map it already had.
