# Kingletas_SectionPolicy

Decide what a private-content invalidation actually invalidates, and report what each action costs.

---

## The problem

Magento serves storefront pages from a cache, so nothing about the shopper can be in the HTML. The mini cart, the greeting, the message banner and a dozen other pieces arrive afterwards over `customer/section/load` — one request, no cache in front of it, a full application bootstrap every time.

The browser decides when to make that request. Every page carries a map of *action → sections it makes stale*, and some actions use `*`, which means **every section**. Logging in is one of them. So is logging out, creating an account, and editing one.

On a stock 2.4 store that means a login refetches all nineteen sections. Eighteen of them are a few hundred bytes together. The nineteenth is `directory-data` — every country and every region the store sells to, around **59 KB**, and **identical for every visitor on earth**. It's store configuration travelling through the one channel that exists because responses cannot be cached.

Nothing in a stock installation reports that this is happening.

---

## What it does

Two things, and the reporting half is the one you use first.

**It reports.** One command prints every action that invalidates everything, what each section costs to produce, and whether your rules can actually work.

**It narrows.** For the actions you name, it expands `*` into the real section list and subtracts the sections you have said that action never needs. A section you have not named keeps being invalidated, so an extension's new section is never dropped by accident.

---

## Installation

```bash
composer require kingletas/module-section-policy
bin/magento module:enable Kingletas_SectionPolicy
bin/magento setup:upgrade
```

**It does nothing until you name a section.** Installing it and changing nothing leaves the map byte-for-byte as it was.

---

## The report

```bash
bin/magento kingletas:section-policy:report
```

```text
  69 action(s) declared, 7 of them invalidate every section, 19 section(s) registered

  +-----------------------------+--------------------+---------------+
  | Action                      | Invalidates now    | After policy  |
  +-----------------------------+--------------------+---------------+
  | customer/account/loginpost  | every section (19) | 18 section(s) |
  | customer/account/logout     | every section (19) | 18 section(s) |
  | stores/store/switch         | every section (19) | unchanged     |
  +-----------------------------+--------------------+---------------+

  +-----------------------------+---------+----------------------------------------+
  | Rule                        | Outcome | Why                                    |
  +-----------------------------+---------+----------------------------------------+
  | customer/account/loginpost  | applies | keeps everything except directory-data |
  +-----------------------------+---------+----------------------------------------+

  policy is in force
```

Add `--measure` and it produces every section once and prints what each one weighs. That's how you find your own `directory-data`:

```text
  +---------------------------+-------+-------+
  | Section                   | Bytes | Share |
  +---------------------------+-------+-------+
  | directory-data            | 59487 | 98%   |
  | cart                      |   375 | 1%    |
  +---------------------------+-------+-------+

  60850 bytes for an anonymous visitor, every section
```

`--measure` runs the section sources, so it's a read of your store rather than of your configuration. The numbers are for an anonymous visitor; a signed-in one carries more.

**The command fails when a rule cannot work** — a section name that does not exist, a section that says who the shopper is, an action no installed module declares. It's quiet when everything is fine, which is the only reason it'll still be read in a month.

---

## Configuration

**Stores → Configuration → Advanced → Section Policy**, or:

```bash
bin/magento config:set kingletas_sectionpolicy/policy/never_invalidated directory-data
bin/magento config:set kingletas_sectionpolicy/policy/enabled 1
bin/magento cache:flush
```

| Setting | Default | What it does |
|---|---|---|
| `policy/enabled` | `0` | Whether the rules are allowed to narrow anything. |
| `policy/never_invalidated` | empty | Comma-separated sections taken out of the four `customer/account/*` actions. |

Both are store-scoped, so one website can narrow and another need not.

---

## What it refuses to do

**It never adds a section to an action.** Every rule is subtraction, so the worst a wrong rule can do is leave things as they were.

**It refuses to drop `customer`, `cart` or `messages`.** Those say who the shopper is and what they're holding, and a stale one of them is another customer's data on the screen. Name one and the report fails rather than applying it.

**It leaves `stores/store/switch`, `stores/store/switchrequest` and `directory/currency/switch` alone**, deliberately. Switching store really can change the countries you sell to, the currency, the prices and the translations — everything genuinely is stale. Only the four account actions ship with a rule.

**It narrows nothing you have not named.** An extension that adds its own invalidate-everything action keeps invalidating everything until somebody adds a rule for it in `di.xml`. The report is what tells you it appeared.

---

## How it works

The browser reads the map from exactly one place — `Magento\Customer\Block\SectionConfig::getSections()`, printed into every page by `Magento_Customer`'s `section-config.phtml`. A plugin on that method is the only interception point, and this module has one.

When a rule applies, it replaces `['*']` with the registered section list minus the exclusions. That's not a change of meaning: `Magento_Customer/js/customer-data` already expands `*` to `sectionConfig.getSectionNames()`, and that list comes from the same block on the same page. Expanding it server-side gives the browser the list it would have built anyway, with the named sections missing.

> [!note] Why `sections.xml` cannot do this
> The obvious approach is to declare the same action in your own `etc/frontend/sections.xml` with an explicit list. **It doesn't work, and it fails silently.** Magento merges config XML by adding, never by replacing, so the merged result is `["*","cart","customer",...]` — the wildcard is still there, the browser stops at it, and the behaviour is identical to having changed nothing. The config visibly changed and nothing else did.

---

## What this does not fix

**The bootstrap.** Most of a `customer/section/load` request is Magento starting up, before any section does anything, and nothing here touches that. Narrowing an invalidation stops you shipping a country list on every login. It doesn't make the request itself faster.

**How often the request happens.** The number of `section/load` calls is unchanged. What changes is what each one carries.

---

## Checks

```bash
make install    # needs repo.magento.com credentials, for magento/framework
make check
```

The coding standard and four test suites — unit, wiring, performance and behaviour. `make test SUITE=behaviour` narrows it. Static analysis and mess detection run in CI, where `composer install` has resolved `magento/framework`.

> [!warning] Adding a plugin to a running developer-mode store
> A plugin only takes effect once Magento regenerates its interception data, and in developer mode the first process to rebuild that data decides it for everyone. A `bin/magento` command rebuilding it first can leave a storefront-only plugin switched off until the next rebuild. After installing, clear `generated/` and let a storefront request be the first thing that hits the store. In production mode `setup:di:compile` computes every area and the question does not arise.
