=== Hamista ===
Contributors: hamista
Requires at least: 6.5
Tested up to: 7.1
Requires PHP: 8.1
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A premium, Persian-first (RTL) theme for creative business studios that sell plugins, themes, software and branding services.

== Description ==

Hamista is the theme of the HAMISTA platform. It owns the design system — design tokens and UI
primitives — that the HAMISTA plugins reuse, so the shop, the customer dashboard and service
orders share one visual language.

* Persian (RTL) first, English (LTR) second: logical CSS properties throughout, mirrored icons
  and gradients, Solar Hijri dates with HAMISTA Core.
* Light and dark colour modes (plus "system"), switched without a flash of the wrong theme.
* Self-hosted Vazirmatn and Inter variable fonts; no Google Fonts, CDNs or other external requests.
* Accessible navigation: skip link, keyboard dropdowns, a focus-trapped mobile drawer, visible
  focus rings, reduced-motion support and AA colour contrast.
* No jQuery; small, deferred, minified assets.
* Works on its own. With HAMISTA Core active, every option is editable in Hamista → Settings.

== Installation ==

1. Upload hamista-theme-1.0.0.zip in Appearance → Themes → Add New → Upload, then activate it.
2. Optional: install and activate HAMISTA Core for the settings panel, content types and components.
3. Assign menus to the Primary, Mobile and Footer locations in Appearance → Menus.

== Frequently Asked Questions ==

= Where is the style guide? =

Logged in as an administrator, open any page with ?hamista-styleguide=1 appended to its URL.

= How do I rebuild the CSS? =

The CSS in assets/css/ is generated from assets/css/src/. Run "npm install" and "npm run build"
in the HAMISTA repository root.

== Changelog ==

= 1.0.0 =
* Initial release.

== Copyright ==

Hamista WordPress Theme, Copyright 2026 HAMISTA.
Hamista is distributed under the terms of the GNU General Public License v2 or later.

This program is free software: you can redistribute it and/or modify it under the terms of the
GNU General Public License as published by the Free Software Foundation, either version 2 of the
License, or (at your option) any later version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without
even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
General Public License for more details.

Hamista bundles the following third-party resources:

Vazirmatn (Non-Latin variable, assets/fonts/vazirmatn-nl-var.woff2)
Copyright 2015 The Vazirmatn Project Authors (https://github.com/rastikerdar/vazirmatn)
License: SIL Open Font License, Version 1.1 — assets/fonts/vazirmatn-OFL.txt
Source: https://github.com/rastikerdar/vazirmatn (npm package vazirmatn 33.0.3)

Inter (Latin variable, assets/fonts/inter-latin-var.woff2)
Copyright 2016 The Inter Project Authors (https://github.com/rsms/inter)
License: SIL Open Font License, Version 1.1 — assets/fonts/inter-OFL.txt
Source: https://github.com/rsms/inter (npm package @fontsource-variable/inter 5.3.0)

Lucide icons (UI icons in inc/class-icons.php)
Copyright (c) 2026 Lucide Icons and Contributors
License: ISC License (https://github.com/lucide-icons/lucide/blob/main/LICENSE)
Icons derived from Feather (arrows, chevrons, check, clock, external-link, monitor, moon, search, x):
Copyright (c) 2013-present Cole Bemis, MIT License
Source: https://lucide.dev (npm package lucide-static 1.48.0)

Simple Icons (brand marks in inc/class-icons.php)
License: CC0 1.0 Universal (https://github.com/simple-icons/simple-icons/blob/develop/LICENSE.md)
Source: https://simpleicons.org (npm package simple-icons 16.33.0; the LinkedIn mark from 9.21.0,
its last release that included it). Brand names and logos are trademarks of their owners.
