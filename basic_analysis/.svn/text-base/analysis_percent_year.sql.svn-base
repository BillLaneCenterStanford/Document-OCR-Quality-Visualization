.head on
.mode csv

-- 'Sample data in table';
--select * from newspaper_count limit 5;

-- 'newspapers with missing location';
--select * from newspaper_count where location='';

-- 'count newspapers with missing location';
--select count(distinct pub) from newspaper_count where location='';

-- 'count newspapers';
--select count(distinct pub) from newspaper_count;

-- 'newspaper and count of issues';
--select pub, count(*) from newspaper_count group by pub order by count(*);

-- 'min and max of dateTime';
--select min(dateTime), max(dateTime), count(dateTime) from newspaper_count;

-- 'verify all fields sum to mTotal';
--select mNumber+mCommon+mProper+mSymbol+mUnknown+mBad as mComputedSum, mTotal
--from newspaper_count where mComputedSum <> mTotal;

-- compute percentage of mBad and mUnknown
--select 100.0*mBad/mTotal as mBadPercent, 100.0*mUnknown/mTotal as mUnknownPercent from newspaper_count;

-- total words by year
--select sum(mTotal), strftime('%Y', dateTime) from newspaper_count group by strftime('%Y', dateTime);

-- best and worst quality of newspapers
--select pub, 100.0*sum(mBad)/sum(mTotal) as mBadPercent from newspaper_count group by pub order by mBadPercent;
--select pub, 100.0*sum(mUnknown)/sum(mTotal) as mUnknownPercent from newspaper_count group by pub order by mUnknownPercent;

-- percentage of mBad, mUnknown over the years
select strftime('%Y', dateTime) as year,
       100.0*sum(mBad)/sum(mTotal) as mBadPercent,
       100.0*sum(mUnknown)/sum(mTotal) as mUnknownPercent,
       100.0*sum(mTotal-mBad-mUnknown)/sum(mTotal) as mOtherPercent,
       sum(mTotal) as total
from newspaper_count group by year;
