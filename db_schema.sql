
/*
drop table if exists newspaper_count;
create table newspaper_count (id text, pub text, city text, state text, dateTime date, mNumber int, mCommon int, mProper int, mSymbol int, mUnknown int, mBad int, mTotal int, pages int, wordsPerPage float);

.import newspaper_count.txt newspaper_count
*/

/*
drop table if exists location;
create table location (zip text, city text, county text, state text, longitude real, latitude real);
.import location.txt location;
*/


/*
select pub, city, state, strftime("%Y", dateTime), sum(mUnknown+mBad), sum(mTotal)
from newspaper_count
group by pub, city, state, strftime("%Y", dateTime);
*/

/*
drop view if exists pub_by_year;
drop view if exists city_by_year;

create view pub_by_year as
select pub, city,
       strftime("%Y", dateTime) as year,
       sum(mTotal-mUnknown-mBad) as mGood,
       sum(mTotal) as mTotal
from newspaper_count
group by pub, city, strftime("%Y", dateTime);

create view city_by_year as
select city,
       strftime("%Y", dateTime) as year,
       sum(mTotal-mUnknown-mBad) as mGood,
       sum(mTotal) as mTotal
from newspaper_count
group by city, strftime("%Y", dateTime);
*/

/*
-- stats by year
select strftime("%Y", dateTime) as year, sum(mTotal) as total, sum(mTotal-mUnknown-mBad) as good
from newspaper_count
group by strftime("%Y", dateTime);
*/

