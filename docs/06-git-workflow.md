# 06 · Git-workflow van het team

Overgenomen uit de oorspronkelijke README.

## GitHub push

voordat je begint doe je:

git switch develop
git pull origin develop
git switch -c feature/NaamVanFeature

dan als je klaar bent doe je git add .
git commit -m "korte naam die uitlegt wat je gedaan hebt"
git push -u origin feature/NaamVanFeature

daarna kan je op github een pull request aanmaken *base: develop* <- compare(je eigen branch)* van develop naar test daar ga je testen werkt dat merge je hem door naar release. eind van elke week word release naar main gemerged.
dan kan iemand de code revieuwen testen en mergen

## GitHub error

als je dit krijgt tijdens het pushen: remote: Invalid username or token. Password authentication is not supported for Git operations. fatal: Authentication failed for '
RensvdBurg/project-in-beweging.git'

doe dan
git credential-manager github login --force
en log in via de browser

## GitHub test

git switch test
git pull origin test
git switch -c fix/NaamVanFix

voor de rest is het hetzelfe als een normale push

## GitHub merge

als je merged maak je ook een pull request aan. Nadat iemand dan je pr heeft gecontroleerd kan hij worden gemerged. we werken op develop. En testen op test. het gaat dus
Develop -> test -> release -> main
als je van develop iets naar test merged moet je er ook voor zorgen dat het op develop komt zodat die up to date blijft.
dit doe je door voor je merged te zorgen dat je base op develop staat en als je dan iets wilt testen merge je develop weer naar test.
