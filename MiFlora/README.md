# MiFLora
Diese Instanz stellt die Geräte dar, welche mit der Tasmota gepairt sind.

## Inhaltverzeichnis
1. [Konfiguration](#1-konfiguration)
2. [Funktionen](#2-funktionen)

## 1. Konfiguration

Feld | Beschreibung
------------ | -------------
Tasmota MQTT Topic| Name des Tasmota Gerätes, ist in den MQTT Einstellungen in der Tasmota Firmware zu finden
Full Topic | Full Topic des Tasmota Gerätes, ist in den MQTT Einstellungen der Tasmota Firmware zu finden
Filtere Daten nach Gerätename | Die eingehenden Daten werden nach dem Gerätenamen gefiltet

###Pflegehinweise
Es können für die Temperatur, die Bodenleitfähigkeit, die Bodenfeuchtigkeit, die Helligkeit und die tägliche Lichtmenge Hinweise ermittelt werden.
Dazu werden die hinterlegten min und max Werte der jeweiligen Messgröße ausgewertet und ein Hinweis auf Unter- bzw. Überschreitung gesetzt. 

Im Einzelnen erfolgt die Auswertung wie folgt:

- Bodenfeuchtigkeit 

Es wird geprüft, ob die aktuelle Bodenfeuchtigkeit kleiner bzw. höher ist als die vorgegebenen Grenzwerte.

- Temperatur

Es wird geprüft, ob die Durchschnittstemperatur der letzten 24 Stunden kleiner bzw. höher ist als die vorgegebenen Grenzwerte.
  
- Bodenleitfähigkeit (Hinweis zum Düngen)

Es wird geprüft, ob die aktuelle Bodenleitfähigkeit kleiner bzw. höher ist als die vorgegebenen Grenzwerte.
Da bei trockenem Pflanzsubstrat die Bodenleitfähigkeit stark abnimmt, wird ein Pflegehinweis nur erstellt, wenn die Bodenfeuchtigkeit höher ist als 30%.
Da in den Monaten Oktober bis Januar üblicherweise nicht gedüngt wird, unterbleibt in diesen Monaten der Hinweis.

- Helligkeit

Es wird geprüft, ob die aktuelle Helligkeit kleiner bzw. höher ist als die vorgegebenen Grenzwerte.
Dabei erfolgt ein Hinweis nur tagsüber. Konkret: in den Monaten November bis März von 10 - 16 Uhr, sonst von 9 - 18 Uhr.

- tägliche Lichtmenge
Die tägliche Lichtmenge wird aus den aufgezeichneten Helligkeitswerten (lx) errechnet. 
Ein Hinweis erfolgt, wenn die tägliche Lichtmenge in den letzten drei Tage außerhalb des vorgegebenen Bereichs lag. 
  



## 2. Funktionen

Keine öffentlichen Funktionen vorhanden.