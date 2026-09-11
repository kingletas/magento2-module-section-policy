# From nothing to a working Kingletas_SectionPolicy

By the end of this you'll know what your own store refetches when somebody logs in, what it costs, and how to stop the part of it that buys nothing.

## Contents

- [What this is](#what-this-is)
- [Step 1: install it](#step-1-install-it)
- [Step 2: find out what your store is doing](#step-2-find-out-what-your-store-is-doing)
- [Step 3: measure the sections](#step-3-measure-the-sections)
- [Step 4: name one section and turn it on](#step-4-name-one-section-and-turn-it-on)
- [Step 5: check the browser agrees](#step-5-check-the-browser-agrees)
- [What you get for free](#what-you-get-for-free)
- [Where to go next](#where-to-go-next)

## What this is

Your storefront pages come out of a cache, so nothing personal can be in them. The mini cart and the greeting arrive afterwards, over a request called `customer/section/load` that no cache can hold.

Some things a shopper does tell the browser that **everything** it holds is out of date. Logging in is one. On a stock store that means the next request carries all nineteen pieces of customer data, including the full list of countries and regions you sell to — which is the same for everybody and is usually most of the response.

This module tells you when that is happening, and lets you stop the part of it that buys nothing.

## Step 1: install it

```bash
composer require kingletas/module-section-policy
```

```bash
bin/magento module:enable Kingletas_SectionPolicy && bin/magento setup:upgrade
```

Nothing has changed on your storefront yet. That's deliberate — the module is inert until you name a section.

## Step 2: find out what your store is doing

```bash
bin/magento kingletas:section-policy:report
```

The first line is the one to read: how many actions are declared, and how many of them invalidate every section. On a stock 2.4 store that's seven. On a store with a few extensions it is often more, and the extra ones are usually the interesting ones.

The table under it names them. `unchanged` in the last column means no rule covers that action yet.

## Step 3: measure the sections

```bash
bin/magento kingletas:section-policy:report --measure
```

This produces every section once and prints what it weighs. Expect one row to be almost the whole total. On a stock store it is `directory-data` at around 98%.

The numbers are for a visitor who is not signed in. A signed-in shopper carries more in `cart` and `customer`, and none of that changes which row is biggest.

## Step 4: name one section and turn it on

Start with the one the measurement found:

```bash
bin/magento config:set kingletas_sectionpolicy/policy/never_invalidated directory-data
```

```bash
bin/magento config:set kingletas_sectionpolicy/policy/enabled 1
```

```bash
bin/magento cache:flush
```

Run the report again. The four `customer/account/*` rows now say `18 section(s)` instead of `every section (19)`, and each rule says what it keeps.

If a rule says `misconfigured`, read the reason — a section name that does not exist, or one the module refuses to drop because it says who the shopper is. The command exits non-zero, so it's safe to put in a deployment check.

## Step 5: check the browser agrees

The report reads the same map the storefront prints, but it's worth seeing it in the page once. Load any storefront page, view source, and search for `section-config`. The action `customer/account/loginpost` should list section names rather than `"*"`, and `directory-data` should not be among them.

If it still says `"*"`, the store hasn't rebuilt its plugin data yet. Clear `generated/` and load a storefront page before running any other `bin/magento` command.

## What you get for free

- A report that is **silent when nothing is wrong** and fails when a rule cannot work, so it is worth putting in a deployment check.
- **Subtraction only.** No rule can add a section to an action, so the worst a mistake does is leave things as they were.
- **Refusal to drop what identifies the shopper.** `customer`, `cart` and `messages` are rejected even when you name them.

## Where to go next

- Add a rule for an action your extensions declare, in `etc/di.xml`. The shipped four are `customer/account/*`; the store and currency switches are deliberately left alone, because switching store really does change everything.
- Put `bin/magento kingletas:section-policy:report --require-enabled` in your deployment checks, so a store that quietly lost the setting says so.
