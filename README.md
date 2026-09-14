# project-in-beweging

Jongeren die leren voor een zittend beroep, zoals programmeren, lopen risico op gezondheidsproblemen door langdurig zitten. De app die wij gaan maken stimuleert gezonde gewoontes door leerdoelen te combineren met korte beweegpauzes.

# Icons

Wil je een icon gebruiken doe dan (iconoir-_naam-icon te vinden op: https://iconoir.com/_)

# install

npm i iconoir
npm install
altijd wanneer je gaat coderen npm run dev

# github push

voordat je begint doe je:

git switch develop
git pull origin develop
git switch -c feature/NaamVanFeature

dan als je klaar bent doe je git add .
git commit -m "korte naam die uitlegt wat je gedaan hebt"
git push -u origin feature/NaamVanFeature

daarna kan je op github een pull request aanmaken
dan kan iemand de code revieuwen testen en mergen

# github error

als je dit krijgt tijdens het pushen: remote: Invalid username or token. Password authentication is not supported for Git operations. fatal: Authentication failed for '
RensvdBurg/project-in-beweging.git'

doe dan
git credential-manager github login --force
en log in via de browser

# github test

git switch test
git pull origin test
git switch -c fix/NaamVanFix

voor de rest is het hetzelfe als een normale push

# github merge

als je merged maak je ook een pull request aan. Nadat iemand dan je pr heeft gecontroleerd kan hij worden gemerged. we werken op develop. En testen op test. het gaat dus
Develop -> test -> release -> main
als je van develop iets naar test merged moet je er ook voor zorgen dat het op develop komt zodat die up to date blijft.
dit doe je door voor je merged te zorgen dat je base op develop staat en als je dan iets wilt testen merge je develop weer naar test.
