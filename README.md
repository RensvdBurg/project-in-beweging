# project-in-beweging

Jongeren die leren voor een zittend beroep, zoals programmeren, lopen risico op gezondheidsproblemen door langdurig zitten. De app die wij gaan maken stimuleert gezonde gewoontes door leerdoelen te combineren met korte beweegpauzes.

#install

npm install
altijd wanneer je gaat coderen npm run dev

voordat je begint doe je:

git switch develop
git pull origin develop
git switch -c feature/NaamVanFeature

dan als je klaar bent doe je git add .
git commiut -m "korte naam die uitlegt wat je gedaan hebt"
git push -u origin feature/NaamVanFeature

daarna kan je op github een pull request aanmaken
dan kan iemand de code revieuwen testen en mergen

als je dit krijgt tijdens het pushen: remote: Invalid username or token. Password authentication is not supported for Git operations. fatal: Authentication failed for '
RensvdBurg/project-in-beweging.git'

doe dan
git credential-manager github login --force
en log in via de browser