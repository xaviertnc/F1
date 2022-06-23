# F1 DB - Changelog

## 24 Jan 2017 - Ver 1.0.0
  - Moved to OneFile

## 07 Mar 2020 - Ver 2.0.0
  - Total refactor!
  - Add db->execRaw()
  - Add db->insertInto()
  - Add db->updateOrInsertInto()
  - Add db->batchUpdate()
  - Add db->arrayIsSingleRow()
  - Add db->indexList()
  - Add db->query()->select()
  - Add db->query()->having()
  - Add db->query()->orHaving()
  - Add db->query()->update()
  - Add db->query()->delete()
  - Add db->query()->build{*.}Sql()
  - Remove db->query()->addExpression()
  - Change QueryStatement to PDOQuery
  - Change QueryExpression to PDOWhere
  - Simplify classes + Change query builder syntax!
  - Simplyfy PDOWhere contructor. No more OPERATOR + GLUE params
  - Re-write build() methods

## 20 Mar 2020 - Ver 3.0.0
  - Significant refactor
  - Replace db->query()->exec()/execRaw()/queryRaw() with db->cmd()
  - Update doc comments

## 15 Aug 2021 - Ver 3.1.0
  - Add db->createTable()
 
## 19 Mar 2022 - Ver 3.2.0
  - Add db->connect()

## 23 Jun 2022 - Ver 3.3.0
  - Rename main class from `Database` to `DB`
  - Change namespace from OneFie to F1
  - Remove unnecessary comments and debug code
  - Move changelogs and the user manual to seperate .md files
  - Fix logic errors in db->updateOrInsertInto()
  - Improve doc comments
