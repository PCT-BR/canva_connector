# Canva Connector for Piwigo

Canva Connector lets the Canva **Piwigo Media** app connect to a Piwigo
instance without sending Piwigo API keys or passwords to a central backend.

## Install

Upload this folder to:

```text
<piwigo-root>/plugins/canva_connector
```

Then open:

```text
https://your-piwigo.example.com/plugins/canva_connector/connect.php
```

while logged in as a Piwigo administrator.

## Connect Canva

1. Review the access warning.
2. Click **Authorize and generate token**.
3. Copy the generated token.
4. Paste it into the Canva Piwigo Media app.

## Permissions

The generated connector token allows Canva Piwigo Media to:

- list albums
- read photos selected for insertion into Canva
- upload Canva exports to a selected album

The token does not expose your Piwigo password or Piwigo API keys.

## Revoke access

Open `connect.php` again and click **Revoke** for the token.

## Related Canva app

The Canva app repository is separate:

https://github.com/PCT-BR/Piwigo-Media-forCanva

Canva listing pages, privacy policy, terms, and support pages are hosted from
the Canva app repository, not from this connector repository.

## Reviewer note

Piwigo is self-hosted software with user-controlled domains. To avoid routing
user media or Piwigo credentials through a central third-party service, this app
uses an open-source connector plugin installed on the user's own Piwigo instance.
The connector generates a local revocable token after the Piwigo administrator
reviews and accepts the requested access.
