# Joomla-Logoleiste

Ein einfaches Joomla-5-Modul für eine kontinuierlich nach links laufende Logoleiste. PHP 8.3 oder höher erforderlich.

## Installation

Die fertige `mod_schlamp_logoleiste-1.1.0.zip` unter [Releases](https://github.com/LorchWeb/mod_schlamp_logoleiste/releases) herunterladen und in Joomla installieren. Die automatisch von GitHub angebotene Quellcode-ZIP ist nicht das Installationspaket.

1. Bilder im Medienmanager nach `images/logoleiste` hochladen.
2. Site-Modul **Schlamp – Logoleiste** veröffentlichen, Templateposition und Menüzuweisung festlegen.
3. Bilderordner, Bildmaße oder sichtbare Anzahl sowie Umlaufdauer einstellen.

Updates über das vorhandene Modul installieren; keine vorherige Deinstallation nötig. Nach Updates gegebenenfalls Joomla- und Browsercache leeren.

## Funktionen

- JPG/JPEG, PNG, WebP, SVG, GIF und AVIF direkt aus dem angegebenen Bilderordner; keine Unterordner.
- Natürliche Sortierung nach Dateinamen. Mit `01-`, `02-` usw. lässt sich die Reihenfolge bestimmen.
- Feste Logofläche, beispielsweise **290 × 150 px**, oder responsive Anzahl pro Desktop/Tablet/Handy (Breite `0`).
- Proportionale Darstellung ohne Beschnitt oder Verzerrung. Leerraum innerhalb der Bilddatei bleibt erhalten.
- Umlaufdauer von 5 bis 300 Sekunden, einstellbare Logoabstände und Außenabstände oben/unten.
- Transparenter Hintergrund; Hintergrundfarbe optional.
- Pause bei Mouse-over, keine sichtbaren Steuerknöpfe.
- Statisches Raster bei Tastaturbedienung, reduzierter Bewegung oder ohne JavaScript.
- Mehrere Modulinstanzen möglich; ein einzelnes Logo bleibt statisch.
- Keine zusätzliche JavaScript-Bibliothek, externe Schnittstelle oder eigene Datenbanktabelle.

Bei fester Breite bestimmt die Modulbreite die sichtbare Anzahl. Ist der Container schmaler als die feste Breite, wird die Logofläche auf die Containerbreite begrenzt. Die Höhe gilt für die Bildfläche; 150 px plus je 10 px Abstand ergibt 170 px Gesamthöhe. Browser- und Seiten-Caches können den Austausch von Bildern verzögert anzeigen.

## Optionale Links

Im Modul eine Zeile pro Bild:

```text
01-partner.png | https://example.com | Partnername
02-verband.svg | /mitgliedschaften | Verband
03-logo.webp | | Alternativtext ohne Link
```

Dateiname muss exakt passen. Die dritte Spalte ist der Alternativtext für Screenreader und fehlende Bilder, kein Tooltip. Ohne passende Zeile bleibt das Bild unverlinkt. Erlaubt sind HTTP/HTTPS und interne Pfade ab Domainwurzel. Kommentarzeilen beginnen mit `#`.

## Entwicklung und Prüfung

Der Modulquellcode liegt direkt im Repository. `tests/` enthält lokale Logik- und Browserprüfungen; PHP verwendet kleine Joomla-API-Stubs und ersetzt keinen Joomla-Integrationstest.

```sh
php tests/verify.php /tmp/logoleiste-preview
node --check media/js/logoleiste.js
node tests/browser.cjs /tmp/logoleiste-preview
```

Unter Windows einen passenden temporären Ordner statt `/tmp/logoleiste-preview` verwenden. Für den Browsertest werden Node.js, das Paket `playwright` und Chromium benötigt. Optional kann `PLAYWRIGHT_CHROMIUM_EXECUTABLE` auf eine vorhandene Chromium-Datei zeigen.

Das Installationspaket enthält ausschließlich Manifest, `services`, `src`, `tmpl`, `language` und `media`. Nach dem Commit lässt es sich aus dem Repository erstellen:

```sh
git archive --format=zip --output=mod_schlamp_logoleiste.zip HEAD mod_schlamp_logoleiste.xml services src tmpl language media
```

Version 1.1.0 wurde lokal mit 34 PHP-Prüfungen und 38 Chromium-Prüfungen getestet. Die Darstellung im jeweiligen Joomla-Template muss zusätzlich geprüft werden.

## Versionen

- **1.1.0**: feste Logobreite, exakte Bildfläche ohne inneren 4-px-Abstand, transparenter/optionaler Hintergrund, Pauseknopf entfernt, lückenlose Wiederholung kurzer Bildfolgen.
- **1.0.0**: ursprüngliche Logoleiste mit Bilderordner, optionalen Links und responsiver Anzahl.

Die Git-Historie beginnt mit dem vorhandenen Stand 1.1.0; ältere Entwicklungsschritte wurden nicht nachträglich rekonstruiert.

## Lizenz

GNU General Public License, Version 2 oder später (`GPL-2.0-or-later`), entsprechend der bestehenden Lizenzangabe im Modul. Siehe [LICENSE](LICENSE). Logos sind nicht enthalten; die Lizenz gilt für den Modulcode.