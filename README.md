# Canva Connector for Piwigo

Piwigo plugin that connects a Piwigo gallery to the Canva Piwigo Media app.

## Features

- Token-based access for the Canva app, without sharing Piwigo credentials or API keys.
- Album and photo listing endpoints for Canva.
- Signed media URLs for faster thumbnail and image loading.
- Cached thumbnail, preview, and insert variants.
- Configurable export dimensions and JPEG quality from the Piwigo admin.
- EXIF orientation handling for generated image variants.
- Canva export upload back into a selected Piwigo album.

## Installation

1. Copy the `canva_connector` folder into the Piwigo `plugins` directory.
2. Activate **Canva Connector** from the Piwigo plugin manager.
3. Open **Canva Connector - Tokens** in the Piwigo admin menu.
4. Generate a token and paste it into the Canva app with the Piwigo base URL shown on the token page.

## Media Settings

Open **Canva Connector - Media settings** in the Piwigo admin menu to tune:

- Canva insert maximum dimension and JPEG quality.
- Preview maximum dimension and JPEG quality.
- Thumbnail maximum dimension and JPEG quality.
- PNG conversion policy.

The default balanced preset is optimized for responsive Canva browsing while keeping inserted images suitable for most design use cases.

## Storage

The connector stores runtime data in Piwigo's `_data` directory:

- `_data/canva_connector_tokens.json`
- `_data/canva_connector_config.json`
- `_data/canva_connector_media_secret`
- `_data/canva_connector_cache/`

Do not commit or publish these runtime files.

## Release

Current release: `1.0.0`.
