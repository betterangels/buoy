---
layout: page
title: Frequently Asked Questions
permalink: /faq/
menu-title: FAQ
---

Some common questions people have asked us about Better Angels/Buoy and our answers to them.

[Read the full FAQ](https://github.com/betterangels/better-angels/wiki/Frequently-Asked-Questions) on the [Buoy project wiki](https://github.com/betterangels/better-angels/wiki/Buoy).

* [General questions](#general-questions)
* [Implementation choices](#implementation-choices)
* [Privacy concerns](#privacy-concerns)
* [Concerns about misuse](#concerns-about-misuse)

# General questions

## Is [[Buoy]] something that can be used on a laptop?

Yes. Buoy can be accessed from any device that has a web browser, including a laptop, desktop, smartphone, or tablet.

## Does Buoy work on Android/iOS/Kindle/your-favorite-platform-here?

Yes. Buoy is designed to work on *any* device that has a web browser.

## Do I need to have my phone with me to use Buoy?

No. You can access Buoy *from any device* that has a web browser, including public kiosks at libraries, computer labs, or Internet cafés.

## Where do I get Buoy? I don't see it in the app store.

Buoy is not an app, it is a tool that adds emergency dispatch services to websites. To use Buoy, you must have a user account on some community-run website, such as those provided by addiction recovery support groups, domestic violence shelters, local volunteer medical collectives, or similar.

If you don't know of any such groups, take a look through our [[List of Buoy-enabled websites]]. Maybe there's a group near you!

If not, consider starting such a group in your town. Reach out to collectives such as [Cop Watch](http://wecopwatch.org/), a local legal aid service provider, [Critical Resistance](http://criticalresistance.org/), [SMART Recovery](http://smartrecovery.org/), the [NCADV](http://www.ncadv.org/), the [Black Cross Health Collective](http://www.blackcrosscollective.org/), or other similar organizations and ask them how you can start a chapter in your neighborhood.

Then get in touch with the [Better Angels](About-Better-Angels) (that's us!) and we'll help you install and set up Buoy for your community.

# Implementation choices

## Why did you make a WordPress plug-in?

Because relatively non-technical people with extremely limited financial resources can easily install, use, and deploy WordPress-based software in the real world. Further, most existing small organizations such as domestic violence shelters, legal aid service sites, and other community-run online centers already use WordPress to provide a web presence. And finally, because Web-based technology delivered to users on-demand is harder for an abuser to detect; since the app needs no installation onto a survivor's phone, there is no app for an abuser who is rifling through the installed apps on their phone to find. (See also [Privacy concerns](#privacy-concerns) for more details.)

### Won't scaling be a problem using WordPress?

Maybe, but remember:

* Buoy is intended to be a *decentralized* and *community-run* system. We believe Buoy will be more effective for the people who need it if a single Buoy instance does not need to support millions of simultaneous users, but rather only several hundred or thousands. This is because, in a group of a million people, there are in fact many distinct "communities." Buoy's design philosophy is to more closely match real-world communities, which are understandably limited in size. We have no intention or desire to create a globalized (and globalizing) tool like Facebook, Twitter, or Google. Their architectures do not accurately reflect most people's social realities or needs.
* The nature of decentralized systems is that they distribute their workload across multiple individual instances. The phrase "many hands make light work" applies here. A single Buoy instance need only handle traffic for its current active users; incoming alerts from other Buoys will bounce the user to that Buoy. (See [our issue tracker](https://github.com/betterangels/better-angels/issues/162) for more information.)
* Also, remember that WordPress itself currently powers approximately 25% of all websites on the Internet, including some extremely large ones. Moreover, [WordPress is evolving in ways that embrace an API geared for performance](http://bethesignal.org/blog/2015/02/27/new-era-wordpress-hhvm-rest-react/).

In short, we believe WordPress's strict dedication to free software matches our philosophy, and its forward-thinking development roadmap gives us the right tools we need to meet our own development goals. But even barring that, there's no reason you can't port the server-side components of Buoy to a platform you like more than WordPress if you want to. (Please do!)

# Privacy concerns

## If I use buoy, will other people be able to track my movements?

No. Buoy never records your location or movements without your explicit permission to do so. The only time Buoy knows where you are is when you press the emergency alert button. Buoy then sends this information only to the people you added to your emergency response team. Your last known location is stored for a maximum of 2 days before being automatically deleted. Buoy immediately ceases transmitting your location the moment you close it.

## Can people I didn't invite see my notifications?

The short answer: No. The emergency alerts Buoy sends are cryptographic hashes (long strings of letters and numbers), not location details or readable messages. When someone on your response team receives an alert, they must first authenticate to the Buoy server before receiving any information about the alert other than the fact that you were its sender.

The longer answer: Unfortunately, Buoy is likely *not secure* against State-level adversaries such as federal government agencies or massive multi-national corporations. However, Buoy is designed to be secure against less sophisticated adversaries, particularly malicious individuals. This includes individuals with superior technical abilities such as police officers or technically-savvy intimate partners.

Buoy's ultimate privacy guarantees depend in part on the security capabilities of the organization(s) who install the Buoy software. Since Buoy is free software and can be installed and administered in a wide variety of environments, we cannot make any claims as to the security or privacy of a specific Buoy server instance.

That said, Buoy closely follows the [National Network to End Domestic Violence's technological safety guides](http://techsafety.org/) and implements many recognized best practices to ensure user safety and privacy. We also encourage you to follow the directions in the [[Buoy Walkthrough]] and our [[Security advice]], along with any on-screen instructions while using Buoy to ensure you have the best and safest experience.

## If my abuser has access to my phone will they know I use Buoy?

Buoy's design intentionally makes it difficult for people to know that you even have a Buoy account. However, no software can totally protect you from someone who has physical access to your phone and the knowledge of how to conduct forensic analysis on its contents. That said, there are numerous steps you can take to hide your use of Buoy from people who have physical access to your phone.

Buoy is unique among crisis response tools of its kind because it does not require installation on your phone. While you *can* create a shortcut to your Buoy panic button screen on your home screen if you want, the same screen is always accessible to you simply by knowing its web address (URL). If you have reason to believe that someone you don't trust has physical access to your phone (and/or your phone's home screen is not protected by a strong passphrase), then we recommend not "installing" the shortcut to Buoy's panic buttons on your phone and instead memorizing the address of your Buoy provider's website. You can access this address using the web browser of *any* device, such as a friend's phone or a public computer at a library; you are not limited to using Buoy with the device on which you created an account.

For even more thorough privacy tips, please see our [[Security advice]] page.

# Concerns about misuse

## What if people send alerts in situations that aren't "real" emergencies?

Although Buoy is designed to be useful in even the most high-risk situations, users can use Buoy however they want. Rather than discourage this, we think Buoy can be useful even when someone may feel like their situation may not rise to the level of calling 9-1-1.

For instance:

* If you feel you are being followed as you walk home on campus, use Buoy. Your friends will be able to watch your location on their screens and quietly chat with you as you walk home, ensuring you reach your destination safely.
* If you or someone you are with feels suicidal, or has a bad trip, and you don't want cops showing up to your house but need assistance, use Buoy. Responders will be notified of your physical location and will be able to coordinate a response action with you and with each-other in real time without ever notifying the authorities of the situation.
* If you are with a group at an outing such as a hike or a large amusement park and get separated from your group, use Buoy. Each group member will be able to see one another's current location on a map, can easily coordinate where to meet up, and can even access turn-by-turn directions to one another's locations with one tap of your finger.

In many situations like these, calling 9-1-1 would actually be a crime, leaving people forced to choose between "doing nothing" and facing off with hostile cops. Using Buoy, even if it's not a "real" emergency, is clearly a better third option. That being said, nothing about Buoy stops you from using additional tools, including 9-1-1 emergency response, to respond to any given situation.

In short, Buoy can be used independently from other tools, or in concert with other resources. It can be useful in extremely dangerous situations, but it can also be useful in relatively mundane cases whenever you need to coordinate with a group of people in physical space. How you use Buoy is really up to you and your community, and we think that's great.

## Will I start getting spam notifications if I use Buoy?

No. Buoy works by enabling you to define who you trust, but not how to contact them. The people you trust must then tell Buoy how best to reach them in case of an emergency. In order for an alert to be sent, you must first invite a specific person to join your response team *and* they must confirm this invitation. We call this process "mutual-verification" because it means only the people you want to notify and who have also opted-in to receive emergency notifications from you will actually receive any messages you send.

Additionally, you can tell Buoy to stop sending notifications to specific people (or to re-start sending specific people notifications) at any time even without removing them from your response team.
