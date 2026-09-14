# Recommended settings

What to set on a production store, and why. Two settings, and one of them is worth far more than the other.

## Contents

- [The short version](#the-short-version)
- [Why enabling it first is safe](#why-enabling-it-first-is-safe)
- [Deciding what goes on the list](#deciding-what-goes-on-the-list)
- [Where the settings can be set](#where-the-settings-can-be-set)
- [Wiring it into a deploy](#wiring-it-into-a-deploy)
- [What to leave alone](#what-to-leave-alone)

## The short version

| Setting | Production value | Why |
|---|---|---|
| `policy/enabled` | `1` | Safe to turn on immediately. It narrows nothing until a section is named, and the three identity sections are refused even when named |
| `policy/never_invalidated` | `directory-data` | Measure first, then set it. This is almost all of the win |

```bash
bin/magento kingletas:section-policy:report --measure
```

```bash
bin/magento config:set kingletas_sectionpolicy/policy/never_invalidated directory-data
```

```bash
bin/magento config:set kingletas_sectionpolicy/policy/enabled 1
```

```bash
bin/magento cache:flush
```

Then run the report again. Every rule should say `applies`.

## Why enabling it first is safe

The two settings are independent, and neither one alone does anything.

`enabled` with an empty list leaves the section map byte-for-byte as it was. A named section with `enabled` off does the same. So there is no ordering hazard: set both, flush, and read the report.

Every rule is subtraction, so the worst a wrong entry can do is leave things as they were. And `customer`, `cart` and `messages` are refused outright, because a stale one of those is another shopper's data on the screen.

## Deciding what goes on the list

`directory-data` is the country and region list. It is store configuration, it is identical for every visitor, and on a stock installation it is around 98% of what a post-login section refetch carries. It is the same answer on almost every store, which is why it is the recommendation rather than an example.

Confirm it on your own store rather than taking it on trust:

```bash
bin/magento kingletas:section-policy:report --measure
```

```text
+---------------------------+-------+-------+
| Section                   | Bytes | Share |
+---------------------------+-------+-------+
| directory-data            | 59487 | 98%   |
| cart                      |   375 | 1%    |
+---------------------------+-------+-------+
```

**A second entry has to pass two tests.** Is the section store configuration rather than anything about the shopper, and is it more than about 5% of the payload?

On every store measured so far the answer to the second is no. Once `directory-data` is gone, everything left totals around a kilobyte, so a second entry buys roughly 1% while introducing a real chance of showing a signed-in shopper something stale.

**So the recommendation is one entry, and a deliberate decision to stop there.**

## Where the settings can be set

Both fields are editable at default and website scope, and not at store view. Values are read at store scope, so a website value falls through to every store view under it.

That is enough for the case that actually comes up: one website narrows, another need not.

## Wiring it into a deploy

`--require-enabled` is what turns the report from a listing into a gate. Without it, a policy that has quietly stopped applying is printed and the command still exits `0`.

```bash
bin/magento kingletas:section-policy:report --require-enabled
```

It exits non-zero when a named section no longer exists, when a rule names a protected section, when no sections are registered at all, or when the policy is switched off.

The first of those is the one that will catch you out. A theme change or a removed module can take a section away, and the report fails rather than ignoring the name.

## What to leave alone

**Do not add a second section just because the list looks short.** The list being short is the design. A section you have not named keeps being invalidated, so an extension's new section is never dropped by accident.

**Do not add a rule for `stores/store/switch` or `directory/currency/switch`.** Switching store really can change the countries you sell to, the currency, the prices and the translations. Everything genuinely is stale there, which is why only the four account actions ship with a rule.

## Where to go next

- [README](../README.md)
