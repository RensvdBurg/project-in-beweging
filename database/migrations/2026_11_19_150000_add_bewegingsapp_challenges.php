<?php

use App\Support\ServerOnlyMigration;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    use ServerOnlyMigration;

    /**
     * Voegt de kolom `description_extra` toe en zet de 50 challenges uit de oude
     * MySQL-database `bewegingsapp` erbij, met hun oorspronkelijke id's.
     *
     * Bewust in een migratie: NativePHP voert op het toestel alleen `migrate --force` uit.
     */
    public function up(): void
    {
        Schema::table('challenges', function (Blueprint $table) {
            $table->text('description_extra')->nullable()->after('description');
        });

        $now = now();

        DB::table('challenges')->insertOrIgnore(array_map(
            fn (array $challenge): array => $challenge + ['description' => null, 'created_at' => $now, 'updated_at' => $now],
            [
                ['id' => 52, 'title' => 'Loop twee minuten op de gang', 'img' => 'images/challenges/unsplash-1476480862126-209bfaa8edc8.jpg', 'kcal_burned' => 8, 'difficulty' => 'Beginner', 'xp_reward' => 10, 'description_extra' => 'Loop twee minuten in een rustig tempo door de gang tijdens een pauze. Houd de doorgang vrij.'],
                ['id' => 53, 'title' => 'Neem een trap in plaats van de lift', 'img' => 'images/challenges/unsplash-1518611012118-696072aa579a.jpg', 'kcal_burned' => 12, 'difficulty' => 'Beginner', 'xp_reward' => 10, 'description_extra' => 'Gebruik een gewone trap voor één verdieping en loop rustig, zonder te haasten.'],
                ['id' => 54, 'title' => 'Wandel vijf minuten tijdens je pauze', 'img' => 'images/challenges/unsplash-1476480862126-209bfaa8edc8.jpg', 'kcal_burned' => 20, 'difficulty' => 'Beginner', 'xp_reward' => 15, 'description_extra' => 'Maak tijdens je pauze een wandeling van vijf minuten door het gebouw of buiten.'],
                ['id' => 55, 'title' => 'Maak een lunchwandeling van tien minuten', 'img' => 'images/challenges/unsplash-1476480862126-209bfaa8edc8.jpg', 'kcal_burned' => 40, 'difficulty' => 'Beginner', 'xp_reward' => 20, 'description_extra' => 'Loop tien minuten in een prettig tempo na het eten en keer op tijd terug.'],
                ['id' => 56, 'title' => 'Marcheer één minuut op de plaats', 'img' => 'images/challenges/unsplash-1518611012118-696072aa579a.jpg', 'kcal_burned' => 5, 'difficulty' => 'Beginner', 'xp_reward' => 5, 'description_extra' => 'Sta op een vrije plek en til afwisselend je knieën op, zonder te stampen.'],
                ['id' => 57, 'title' => 'Doe twintig schouderrollen', 'img' => 'images/challenges/unsplash-1518611012118-696072aa579a.jpg', 'kcal_burned' => 3, 'difficulty' => 'Beginner', 'xp_reward' => 5, 'description_extra' => 'Rol je schouders tien keer naar voren en tien keer naar achteren, rustig en gecontroleerd.'],
                ['id' => 58, 'title' => 'Rek je nek zachtjes links en rechts', 'img' => 'images/challenges/unsplash-1506126613408-eca07ce68773.jpg', 'kcal_burned' => 2, 'difficulty' => 'Beginner', 'xp_reward' => 5, 'description_extra' => 'Kantel je hoofd zachtjes naar beide schouders en houd elke kant tien seconden vast; forceer niets.'],
                ['id' => 59, 'title' => 'Knijp vijftien keer je schouderbladen samen', 'img' => 'images/challenges/unsplash-1571019613454-1cb2f99b2d8b.jpg', 'kcal_burned' => 3, 'difficulty' => 'Beginner', 'xp_reward' => 5, 'description_extra' => 'Zit of sta rechtop, trek je schouderbladen kort naar elkaar toe en ontspan weer.'],
                ['id' => 60, 'title' => 'Draai tien keer je armen naar voren', 'img' => 'images/challenges/unsplash-1518611012118-696072aa579a.jpg', 'kcal_burned' => 3, 'difficulty' => 'Beginner', 'xp_reward' => 5, 'description_extra' => 'Maak met gestrekte armen kleine cirkels naar voren op een plek met voldoende ruimte.'],
                ['id' => 61, 'title' => 'Draai tien keer je armen naar achteren', 'img' => 'images/challenges/unsplash-1518611012118-696072aa579a.jpg', 'kcal_burned' => 3, 'difficulty' => 'Beginner', 'xp_reward' => 5, 'description_extra' => 'Maak met gestrekte armen kleine cirkels naar achteren; houd afstand van anderen.'],
                ['id' => 62, 'title' => 'Draai je polsen twintig keer', 'img' => 'images/challenges/unsplash-1497366811353-6870744d04b2.jpg', 'kcal_burned' => 2, 'difficulty' => 'Beginner', 'xp_reward' => 5, 'description_extra' => 'Draai elke pols tien keer met de klok mee en tien keer tegen de klok in.'],
                ['id' => 63, 'title' => 'Open en sluit je handen dertig keer', 'img' => 'images/challenges/unsplash-1497366811353-6870744d04b2.jpg', 'kcal_burned' => 2, 'difficulty' => 'Beginner', 'xp_reward' => 5, 'description_extra' => 'Strek je vingers wijd uit en maak daarna rustig een vuist; herhaal dertig keer.'],
                ['id' => 64, 'title' => 'Strek je armen tien keer uit', 'img' => 'images/challenges/unsplash-1518611012118-696072aa579a.jpg', 'kcal_burned' => 2, 'difficulty' => 'Beginner', 'xp_reward' => 5, 'description_extra' => 'Strek beide armen rustig voor je uit en buig je ellebogen weer; herhaal tien keer.'],
                ['id' => 65, 'title' => 'Draai je bovenlichaam zittend tien keer', 'img' => 'images/challenges/unsplash-1497366811353-6870744d04b2.jpg', 'kcal_burned' => 3, 'difficulty' => 'Beginner', 'xp_reward' => 5, 'description_extra' => 'Zit rechtop en draai je romp afwisselend rustig naar links en rechts zonder te forceren.'],
                ['id' => 66, 'title' => 'Buig zittend tien keer zijwaarts', 'img' => 'images/challenges/unsplash-1497366811353-6870744d04b2.jpg', 'kcal_burned' => 3, 'difficulty' => 'Beginner', 'xp_reward' => 5, 'description_extra' => 'Zit stevig op je stoel en buig langzaam afwisselend naar links en rechts.'],
                ['id' => 67, 'title' => 'Til zittend je knieën twintig keer op', 'img' => 'images/challenges/unsplash-1497366811353-6870744d04b2.jpg', 'kcal_burned' => 4, 'difficulty' => 'Beginner', 'xp_reward' => 7, 'description_extra' => 'Til je knieën om de beurt een klein stukje van de grond terwijl je rechtop zit.'],
                ['id' => 68, 'title' => 'Hef zittend je hielen twintig keer', 'img' => 'images/challenges/unsplash-1497366811353-6870744d04b2.jpg', 'kcal_burned' => 3, 'difficulty' => 'Beginner', 'xp_reward' => 5, 'description_extra' => 'Laat je tenen op de grond staan en breng je hielen twintig keer omhoog.'],
                ['id' => 69, 'title' => 'Til zittend je tenen twintig keer op', 'img' => 'images/challenges/unsplash-1497366811353-6870744d04b2.jpg', 'kcal_burned' => 2, 'difficulty' => 'Beginner', 'xp_reward' => 5, 'description_extra' => 'Houd je hielen op de vloer en til de voorkant van je voeten twintig keer op.'],
                ['id' => 70, 'title' => 'Draai beide enkels tien keer', 'img' => 'images/challenges/unsplash-1497366811353-6870744d04b2.jpg', 'kcal_burned' => 2, 'difficulty' => 'Beginner', 'xp_reward' => 5, 'description_extra' => 'Til één voet iets op en draai je enkel tien keer; wissel daarna van voet.'],
                ['id' => 71, 'title' => 'Sta tien keer op uit je stoel', 'img' => 'images/challenges/unsplash-1518611012118-696072aa579a.jpg', 'kcal_burned' => 7, 'difficulty' => 'Beginner', 'xp_reward' => 10, 'description_extra' => 'Sta vanuit een stevige stoel tien keer rustig op en ga weer zitten, zonder je te haasten.'],
                ['id' => 72, 'title' => 'Doe vijftien halve squats', 'img' => 'images/challenges/unsplash-1571019613454-1cb2f99b2d8b.jpg', 'kcal_burned' => 8, 'difficulty' => 'Gevorderd', 'xp_reward' => 10, 'description_extra' => 'Zak op een vrije plek vijftien keer licht door je knieën; houd je rug neutraal.'],
                ['id' => 73, 'title' => 'Ga tien keer op je tenen staan', 'img' => 'images/challenges/unsplash-1518611012118-696072aa579a.jpg', 'kcal_burned' => 4, 'difficulty' => 'Beginner', 'xp_reward' => 5, 'description_extra' => 'Sta naast een stabiel bureau of een muur en kom tien keer gecontroleerd omhoog op je tenen.'],
                ['id' => 74, 'title' => 'Rek je kuiten aan de muur', 'img' => 'images/challenges/unsplash-1571019613454-1cb2f99b2d8b.jpg', 'kcal_burned' => 3, 'difficulty' => 'Beginner', 'xp_reward' => 5, 'description_extra' => 'Plaats je handen tegen een muur, zet één voet achteruit en rek elke kuit twintig seconden.'],
                ['id' => 75, 'title' => 'Strek je hamstrings één minuut', 'img' => 'images/challenges/unsplash-1571019613454-1cb2f99b2d8b.jpg', 'kcal_burned' => 3, 'difficulty' => 'Beginner', 'xp_reward' => 5, 'description_extra' => 'Zet één hak iets naar voren en buig vanuit je heupen; houd dertig seconden per been vast.'],
                ['id' => 76, 'title' => 'Rek de voorkant van je bovenbenen', 'img' => 'images/challenges/unsplash-1571019613454-1cb2f99b2d8b.jpg', 'kcal_burned' => 3, 'difficulty' => 'Beginner', 'xp_reward' => 5, 'description_extra' => 'Houd je vast aan een stevige steun en breng je hiel rustig richting je bil; wissel na twintig seconden.'],
                ['id' => 77, 'title' => 'Open je borstspieren bij een deurpost', 'img' => 'images/challenges/unsplash-1518611012118-696072aa579a.jpg', 'kcal_burned' => 2, 'difficulty' => 'Beginner', 'xp_reward' => 5, 'description_extra' => 'Zet je onderarm zacht tegen een deurpost en draai iets weg; wissel na twintig seconden.'],
                ['id' => 78, 'title' => 'Doe tien push-ups tegen de muur', 'img' => 'images/challenges/unsplash-1571019613454-1cb2f99b2d8b.jpg', 'kcal_burned' => 6, 'difficulty' => 'Beginner', 'xp_reward' => 10, 'description_extra' => 'Plaats je handen op schouderhoogte tegen een stevige muur en buig en strek tien keer.'],
                ['id' => 79, 'title' => 'Doe tien kleine uitvalpassen', 'img' => 'images/challenges/unsplash-1571019613454-1cb2f99b2d8b.jpg', 'kcal_burned' => 9, 'difficulty' => 'Gevorderd', 'xp_reward' => 10, 'description_extra' => 'Zet afwisselend een korte stap naar achteren en buig licht door je knieën, vijf keer per been.'],
                ['id' => 80, 'title' => 'Stap twintig keer opzij', 'img' => 'images/challenges/unsplash-1518611012118-696072aa579a.jpg', 'kcal_burned' => 6, 'difficulty' => 'Beginner', 'xp_reward' => 7, 'description_extra' => 'Doe op een vrije plek twintig rustige zijstappen, tien naar elke kant.'],
                ['id' => 81, 'title' => 'Werk twee minuten staand', 'img' => 'images/challenges/unsplash-1497366754035-f200968a6e72.jpg', 'kcal_burned' => 3, 'difficulty' => 'Beginner', 'xp_reward' => 5, 'description_extra' => 'Voer, als je werkplek dat toelaat, twee minuten een gewone taak staand uit.'],
                ['id' => 82, 'title' => 'Span je bilspieren vijftien keer aan', 'img' => 'images/challenges/unsplash-1497366811353-6870744d04b2.jpg', 'kcal_burned' => 2, 'difficulty' => 'Beginner', 'xp_reward' => 5, 'description_extra' => 'Span terwijl je zit je bilspieren telkens twee seconden aan en laat dan los.'],
                ['id' => 83, 'title' => 'Span je buikspieren tien keer kort aan', 'img' => 'images/challenges/unsplash-1497366811353-6870744d04b2.jpg', 'kcal_burned' => 2, 'difficulty' => 'Beginner', 'xp_reward' => 5, 'description_extra' => 'Zit rechtop, span je buik licht aan gedurende drie seconden en adem normaal door.'],
                ['id' => 84, 'title' => 'Controleer één minuut je zithouding', 'img' => 'images/challenges/unsplash-1497366811353-6870744d04b2.jpg', 'kcal_burned' => 2, 'difficulty' => 'Beginner', 'xp_reward' => 5, 'description_extra' => 'Zet je voeten plat neer, ontspan je schouders en plaats je scherm op een prettige kijkhoogte.'],
                ['id' => 85, 'title' => 'Kijk dertig seconden in de verte', 'img' => 'images/challenges/unsplash-1506126613408-eca07ce68773.jpg', 'kcal_burned' => 0, 'difficulty' => 'Beginner', 'xp_reward' => 5, 'description_extra' => 'Kijk even weg van je scherm naar een punt in de verte om je ogen rust te geven.'],
                ['id' => 86, 'title' => 'Loop naar de waterkraan en terug', 'img' => 'images/challenges/unsplash-1532635241-17e820acc59f.jpg', 'kcal_burned' => 6, 'difficulty' => 'Beginner', 'xp_reward' => 7, 'description_extra' => 'Sta op, loop rustig naar een waterpunt, vul je fles en loop terug naar je plek.'],
                ['id' => 87, 'title' => 'Doe een lopend overleg van vijf minuten', 'img' => 'images/challenges/unsplash-1497366754035-f200968a6e72.jpg', 'kcal_burned' => 20, 'difficulty' => 'Beginner', 'xp_reward' => 15, 'description_extra' => 'Bespreek een kort onderwerp wandelend met een collega, als de situatie dat toelaat.'],
                ['id' => 88, 'title' => 'Ruim drie minuten staand je bureau op', 'img' => 'images/challenges/unsplash-1497366754035-f200968a6e72.jpg', 'kcal_burned' => 9, 'difficulty' => 'Beginner', 'xp_reward' => 10, 'description_extra' => 'Berg losse spullen op en maak je werkplek overzichtelijk terwijl je staat.'],
                ['id' => 89, 'title' => 'Loop drie minuten naar buiten', 'img' => 'images/challenges/unsplash-1476480862126-209bfaa8edc8.jpg', 'kcal_burned' => 12, 'difficulty' => 'Beginner', 'xp_reward' => 10, 'description_extra' => 'Ga tijdens je pauze drie minuten naar buiten en loop een klein rondje.'],
                ['id' => 90, 'title' => 'Doe twintig rustige hak-heffingen staand', 'img' => 'images/challenges/unsplash-1518611012118-696072aa579a.jpg', 'kcal_burned' => 6, 'difficulty' => 'Beginner', 'xp_reward' => 7, 'description_extra' => 'Sta rechtop naast een stevige steun en til je hielen twintig keer langzaam omhoog.'],
                ['id' => 91, 'title' => 'Loop één minuut op je tenen en hielen', 'img' => 'images/challenges/unsplash-1518611012118-696072aa579a.jpg', 'kcal_burned' => 5, 'difficulty' => 'Gevorderd', 'xp_reward' => 10, 'description_extra' => 'Wissel op een veilige, vlakke plek korte stukjes op je tenen en je hielen af.'],
                ['id' => 92, 'title' => 'Doe dertig zijstap-tikken', 'img' => 'images/challenges/unsplash-1518611012118-696072aa579a.jpg', 'kcal_burned' => 7, 'difficulty' => 'Beginner', 'xp_reward' => 7, 'description_extra' => 'Stap afwisselend opzij en tik met je andere voet bij; herhaal dertig keer.'],
                ['id' => 93, 'title' => 'Houd dertig seconden een wall sit vast', 'img' => 'images/challenges/unsplash-1571019613454-1cb2f99b2d8b.jpg', 'kcal_burned' => 5, 'difficulty' => 'Gevorderd', 'xp_reward' => 10, 'description_extra' => 'Leun met je rug tegen een stevige muur en zak licht door je knieën; houd maximaal dertig seconden vast.'],
                ['id' => 94, 'title' => 'Balanceer twintig seconden per been', 'img' => 'images/challenges/unsplash-1518611012118-696072aa579a.jpg', 'kcal_burned' => 2, 'difficulty' => 'Gevorderd', 'xp_reward' => 10, 'description_extra' => 'Sta naast een muur, til één voet op en blijf twintig seconden stabiel staan; wissel van been.'],
                ['id' => 95, 'title' => 'Open je heupen tien keer per kant', 'img' => 'images/challenges/unsplash-1571019613454-1cb2f99b2d8b.jpg', 'kcal_burned' => 4, 'difficulty' => 'Beginner', 'xp_reward' => 7, 'description_extra' => 'Til één knie licht op en beweeg die rustig naar buiten; herhaal tien keer per kant.'],
                ['id' => 96, 'title' => 'Breng je been tien keer naar achteren', 'img' => 'images/challenges/unsplash-1571019613454-1cb2f99b2d8b.jpg', 'kcal_burned' => 5, 'difficulty' => 'Beginner', 'xp_reward' => 7, 'description_extra' => 'Houd je aan een stevige steun vast en beweeg elk been tien keer gecontroleerd naar achteren.'],
                ['id' => 97, 'title' => 'Til je been tien keer zijwaarts', 'img' => 'images/challenges/unsplash-1571019613454-1cb2f99b2d8b.jpg', 'kcal_burned' => 5, 'difficulty' => 'Beginner', 'xp_reward' => 7, 'description_extra' => 'Sta rechtop bij een muur en til elk been tien keer een klein stukje opzij.'],
                ['id' => 98, 'title' => 'Fiets vijf minuten in je pauze', 'img' => 'images/challenges/unsplash-1552674605-db6ffd4facb5.jpg', 'kcal_burned' => 30, 'difficulty' => 'Beginner', 'xp_reward' => 15, 'description_extra' => 'Maak, als je een fiets beschikbaar hebt, een rustig rondje van vijf minuten op een veilige route.'],
                ['id' => 99, 'title' => 'Neem een actieve rekpauze van drie minuten', 'img' => 'images/challenges/unsplash-1518611012118-696072aa579a.jpg', 'kcal_burned' => 7, 'difficulty' => 'Beginner', 'xp_reward' => 10, 'description_extra' => 'Wissel drie minuten rustig schouder-, arm- en kuitrekoefeningen af.'],
                ['id' => 100, 'title' => 'Doe tien langzame squats', 'img' => 'images/challenges/unsplash-1571019613454-1cb2f99b2d8b.jpg', 'kcal_burned' => 7, 'difficulty' => 'Gevorderd', 'xp_reward' => 10, 'description_extra' => 'Zak tien keer gecontroleerd door je knieën op een vrije plek; ga niet dieper dan prettig voelt.'],
                ['id' => 101, 'title' => 'Maak een frisse-neus-rondje van twee minuten', 'img' => 'images/challenges/unsplash-1476480862126-209bfaa8edc8.jpg', 'kcal_burned' => 8, 'difficulty' => 'Beginner', 'xp_reward' => 10, 'description_extra' => 'Loop tijdens een korte pauze twee minuten rustig buiten en keer daarna terug.'],
            ],
        ));
    }

    public function down(): void
    {
        DB::table('challenges')->whereBetween('id', [52, 101])->delete();

        Schema::table('challenges', function (Blueprint $table) {
            $table->dropColumn('description_extra');
        });
    }
};
