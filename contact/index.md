---
title: Connect with Us
weight: 20
---
{% assign org = site.data.team.organizations.betterangels %}
# Social media

* @{% include icon-twitter.html username=org.twitter %}
* [Facebook](https://www.facebook.com/{{ org.facebook }})
* [Tumblr](https://{{ org.tumblr }}.tumblr.com/)
* {% include icon-diaspora.html profile_url=org.diaspora %}[Diaspora]({{ org.diaspora }})

# Email

* <span class="glyphicon glyphicon-envelope"></span> Our email address: [{{ site.data.team.organizations.betterangels.email }}](mailto:{{ site.data.team.organizations.betterangels.email }})
* <span class="glyphicon glyphicon-lock"></span> PGP/GPG key: `6121 4D68 E0E3 54AA DE65 3B15 6FAE 063A 2F94 2A02`
* <span class="glyphicon glyphicon-bullhorn"></span> Join our low-volume announcement list:
  <form class="bold_label" action="https://lists.riseup.net/www" method="post">
    <fieldset>
        <label for="email">Your e-mail address</label>
        <input name="email" size="30" type="text" />
        <input name="list" value="betterangels-announce" type="hidden" />
        <input name="action" value="subrequest" type="hidden" />
        <input name="via_subrequest" value="1" type="hidden" />
        <input name="action_subrequest" value="submit" type="submit" />
    </fieldset>
  </form>

# Chat

* <span class="glyphicon glyphicon-phone"></span> [Better Angels' Buoy project chat room](https://gitter.im/{{ site.github.repository_nwo }}/)
