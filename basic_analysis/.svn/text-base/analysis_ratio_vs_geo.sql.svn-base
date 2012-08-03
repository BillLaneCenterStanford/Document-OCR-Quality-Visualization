
/*
create table newspaper (
id text, pub text, city text, state text, zip text,
longitude real, latitude real,
dateTime date,
mNumber int, mCommon int, mProper int, mSymbol int, mUnknown int, mBad int, mTotal int
);

insert into newspaper
select
newspaper_count.id, newspaper_count.pub, newspaper_count.city, newspaper_count.state, location.zip,
location.longitude, location.latitude,
newspaper_count.dateTime,
newspaper_count.mNumber, newspaper_count.mCommon, newspaper_count.mProper, newspaper_count.mSymbol, newspaper_count.mUnknown, newspaper_count.mBad, newspaper_count.mTotal
from newspaper_count, location
where newspaper_count.city=location.city and newspaper_count.state=location.state;
*/

select id, pub, city, state, zip, longitude, latitude, dateTime, 1.0*(mTotal-mUnknown-mBad)/mTotal, 1.0*(mUnknown+mBad)/mTotal from newspaper;
