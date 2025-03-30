# OndeAbastecer
Consiste em uma integração BigQuery para trazer postos de combustiveis com preços mais em conta
"# OndeAbastecer_1" 



# OndeAbastecer
Consiste em uma integração BigQuery para trazer postos de combustiveis com preços mais em conta

git filter-branch --force --index-filter \
"git rm --cached --ignore-unmatch credentials.json" \
--prune-empty --tag-name-filter cat -- --all




echo "# OndeAbastecer_1" >> README.md
git init
git add README.md
git commit -m "first commit"
git branch -M main
git remote add origin https://github.com/amorimgg7/OndeAbastecer_1.git
git push -u origin main




git checkout -b main
git push origin main


git rm --cached ondeabastecer_1_0.rar
git commit --amend -C HEAD
git push origin minha-feature --force



git rm --cached credentials.json
git commit --amend -C HEAD
git push origin minha-feature --force



git filter-branch --force --index-filter \
"git rm --cached --ignore-unmatch credentials.json" \
--prune-empty --tag-name-filter cat -- --all
git push origin --force --all


