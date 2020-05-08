
drop table if exists migrate_user_option;
create table migrate_user_option as
select
	userID,
	IF(userOption99 = 'Ausland', '-Ausland-',
	IF(userOption99 = 'Bundesweit', '-Bundesweit-',
		 userOption99)) as userOptionValue
from wcf1_user_option_value
where userOption99 <> '-';

update wcf1_user_option_value
join migrate_user_options using (userID)
set userOption51 = userOptionValue;

insert into wcf1_pinehearst_registry_entry (userID,locationID,registeredOn)
select a.userID, l.locationID, a.since
from astor_citizenship_cards a
join wcf1_pinehearst_registry_location l on (l.locationName = a.homestate)
where a.mainID is null;

insert into wcf1_pinehearst_registry_entry (userID,locationID,registeredOn,parentID)
select a.userID, l.locationID, a.since, e.entryID
from astor_citizenship_cards a
join wcf1_pinehearst_registry_location l on (l.locationName = a.homestate)
join wcf1_pinehearst_registry_entry e on (e.userID = a.mainID)
where a.mainID is not null;

drop temporary table if exists user_board_posts;
create temporary table user_board_posts as
select e.userID, b.boardID, max(p.postID) as postID
from wcf1_pinehearst_registry_entry e
join wbb1_1_post p using (userID)
join wbb1_1_thread t using (threadID)
join wbb1_1_board b using (boardID)
where b.countUserPosts = 1
group by e.userID, b.boardID;

drop temporary table if exists user_location_posts;
create temporary table user_location_posts as
select l.locationID, p.userID, max(p.postID) as postID
from wcf1_pinehearst_registry_location l
join wbb1_1_board b on (b.boardID = l.boardID or b.parentID = l.boardID)
join user_board_posts p on (p.boardID = b.boardID)
group by l.locationID, p.userID;

drop temporary table if exists user_posts;
create temporary table user_posts as
select userID, max(postID) as postID
from user_board_posts
group by userID;

update wcf1_pinehearst_registry_entry e
join user_posts p using (userID)
set e.postID = p.postID
where e.parentID is null;

update wcf1_pinehearst_registry_entry e
join user_location_posts p using (userID, locationID)
set e.postID = p.postID
where e.parentID is not null;

update wcf1_pinehearst_registry_entry e
join wbb1_1_post p using (postID)
set e.postID = null
where p.time <= e.registeredOn;
