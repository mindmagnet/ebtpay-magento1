# eBTPay for Magento 1 by MindMagnet #
## Version: 1.8.3 ##

Modulul de Magento 1 eBTPay funcționează pe modelul Authorize-Capture/Void, deci la plasarea comenzii suma este autorizată (debitată de pe cardul clientului), iar la livrare ea este capturată (virată în contul comerciantului); în caz de imposibilitate de livrare, în loc de capturare comanda este anulată (void) iar banii sunt returnați clientlui. Există și posibilitatea void-ului după capturare. Este implementat pe Magento 1.9.0.0 - 1.9.4.0 și are compatibilitatea verificata pe toate versiunile 1.7.x - 1.8.x.

## Install ##

1. Creati un director numit .modman in directorul radacina Magento
2. Clonati modulul
3. Rulati comanda modman deploy-all

## Manual de utilizare pentru Magento eBTPay ##

Modulul de Magento eBTPay funcționează pe modelul Authorize-Capture/Void, deci la plasarea
comenzii suma este autorizată (debitată de pe cardul clientului), iar la livrare ea este capturată (virată
în contul comerciantului); în caz de imposibilitate de livrare, în loc de capturare comanda este anulată
(void) iar banii sunt returnați clientlui. Există și posibilitatea void-ului după capturare.

### Autorizare ###

La plasarea comenzii, metodele de plată eBTPay sunt afișate clientului iar după plasarea comenzii
acesta este redirectat la pagina de plată RomCard unde va introduce datele de card; cardurile
înrolate vor fi duse la pasul de 3D secure, iar după finalizarea plății, utilizatorul este întors la site-ul
comerciantului.
În panoul de administrare Magento, comanda astfel plasată va avea în secțiunea de comentarii
rezultatul autorizării cu mesajul de succes sau eventual cel de eroare.

### Capturarea ###

Pentru a captura o sumă, trebuie folosită funcția standard de Invoice a Magento: din interiorul
comenzii se alege opțiunea de Invoice, se urmează pașii din invoice (cu cantități integrale sau
parțiale), iar suma respectivă (mai mică sau egală cu suma autorizată) va fi captată. Este important ca
opțiunea de “Amount” să fie setată la “Capture online”. Ca urmare a captării, mesajul de succes apare
în secțiunea de comentarii. În cazul unei erori, mesajul de eroare este afișat pe ecran.

### Anularea unei autorizări ###

Anularea unei comenzi care a fost autorizată cu succes dar nu a fost capturată, se face prin funcția de
Cancel. Ca urmare a anulării, mesajul de succes apare în secțiunea de comentarii a comenzii. În cazul
unei erori, mesajul de eroare este afișat pe ecran.

### Anularea unei capturări ###

Dacă s-a efectuat capturarea comenzii și se dorește un refund, acest lucru se face utilizând funcția
de Credit Memo. Este important să nu se utilizeze butonul de Credit Memo din setul de butoane din
order, deoarece respectivul Credit Memo va fi offline. Pentru a efectua un Credit Memo se intră în
invoice-ul generat pentru capturare și se selectează Credit Memo din acel ecran, iar din ecranul de
Credit Memo se face un Refund (nu “Refund offline”). Ca urmare a anulării, mesajul de succes apare
în secțiunea de comentarii a comenzii. În cazul unei erori, mesajul de eroare este afișat pe ecran.

## Probleme & Contributie ##

Dacă depistați o problemă cu funcționarea modulului, puteți raporta un issue în tab-ul Issues din GitHub-ul acestui modul.
Dacă sunteți developer și realizați fixuri pe acest modul, rapoartate sau nu în Issues, puteți clona modulul și face un pull request
