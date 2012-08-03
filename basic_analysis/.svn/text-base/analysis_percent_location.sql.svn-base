.head on
.mode csv

-- best and worst quality of newspapers
select location,
       100.0*sum(mBad)/sum(mTotal) as mBadPercent,
       100.0*sum(mUnknown)/sum(mTotal) as mUnknownPercent,
       100.0*sum(mTotal-mBad-mUnknown)/sum(mTotal) as mOther,
       sum(mTotal) as total
from newspaper_count group by location;

--select count(distinct location) from newspaper_count;

