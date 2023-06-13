"""
    Copyright (c) 2015-2019 Ad Schellevis <ad@opnsense.org>
    All rights reserved.

    Redistribution and use in source and binary forms, with or without
    modification, are permitted provided that the following conditions are met:

    1. Redistributions of source code must retain the above copyright notice,
     this list of conditions and the following disclaimer.

    2. Redistributions in binary form must reproduce the above copyright
     notice, this list of conditions and the following disclaimer in the
     documentation and/or other materials provided with the distribution.

    THIS SOFTWARE IS PROVIDED ``AS IS'' AND ANY EXPRESS OR IMPLIED WARRANTIES,
    INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY
    AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
    AUTHOR BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY,
    OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
    SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
    INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
    CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
    ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
    POSSIBILITY OF SUCH DAMAGE.

    --------------------------------------------------------------------------------------

    package : configd
"""

import os
import glob
import collections
import netaddr


class SortKeyHelper:
    """ generate item key for sort function
    """
    def __init__(self, fields):
        """ initialize SortKeyHelper

        :param fields: field names
        """
        self._fields = fields

    def get_key(self, record):
        """ initialize SortKeyHelper

        :param fields: dictionary item
        :return: list of keys for this record
        """
        result = list()
        for field in self._fields:
            if field in record:
                result.append(record[field])
            else:
                result.append('')
        return result


# noinspection PyPep8Naming
class Helpers(object):
    def __init__(self, template_in_data):
        """ initialize template helpers

        :param template_in_data: configuration data used by the engine
        :return:
        """
        self._template_in_data = template_in_data

    def getNodeByTag(self, tag):
        """ get tree node by tag
        :param tag: tag in dot notation (section.item)
        :return: dict or None if not found
        """
        node = self._template_in_data
        for item in tag.split('.'):
            if item in node:
                node = node[item]
            else:
                # not found
                return None
        # path found, return
        return node

    def exists(self, tag):
        """
        check if node exists in dictionary structure
        :param tag: tag in dot notation (section.item)
        :return: boolean
        """
        if self.getNodeByTag(tag):
            return True
        else:
            return False

    def empty(self, tag):
        """ check if either the node does not exist or is empty
        :param tag: tag in dot notation (section.item)
        :return: boolean
        """
        node = self.getNodeByTag(tag)
        if node is None:
            return True
        elif len(node) == 0:
            return True
        elif hasattr(node, 'strip') and node.strip() in ('', '0'):
            return True
        else:
            return False

    def toList(self, tag, sortBy=None, sortAs=None):
        """ if an item should be a list of items (repeating tag), use this method to make sure that we always return
            a list. The configuration doesn't know if a non repeating item is supposed to be a list of items, this makes
            it explicit.
        :param tag: tag in dot notation (section.item)
        :param sortBy: resort result by specified key
        :return: []
        """
        result = self.getNodeByTag(tag)
        if result is None:
            return []
        if type(result) != list:
            # wrap result
            result = [result]

        if sortBy is None:
            return result
        else:
            # resort list by tag
            if sortAs == 'int':
                return sorted(result, key=lambda d: int(d[sortBy]))
            else:
                return sorted(result, key=lambda d: d[sortBy])

    def getUUIDtag(self, uuid):
        """ retrieve tag name of registered uuid, returns __not_found__ if none available
        :param uuid:
        :return: string
        """
        if uuid in self._template_in_data['__uuid_tags__']:
            return self._template_in_data['__uuid_tags__'][uuid]
        else:
            return "__not_found__"

    def getUUID(self, uuid):
        """ retrieve item by uuid if found
        :param uuid:
        :return: dict
        """
        if uuid in self._template_in_data['__uuid__']:
            return self._template_in_data['__uuid__'][uuid]
        else:
            return {}

    def getNodeAsList(self, node):
        obj = self.getNodeByTag(node)
        if obj:
            if isinstance(obj, list):
                return obj
            return [ obj ]
        return []

    def isRPZEnabled(self):
        for rpz in self.getNodeAsList('OPNsense.RPZ.FilteringList.lists.list'):
            if rpz and 'enabled' in rpz and rpz['enabled'] == '1':
                return True
        return False

    def isRPZWhitelistEnabled(self):
        for wl in self.getNodeAsList('OPNsense.RPZ.WhiteList.entries.entry'):
            if wl and 'enabled' in wl and wl['enabled'] == '1':
                return True
        return False

    def _getRPZTags(self):
        result = []
        for rpz in self.getNodeAsList('OPNsense.RPZ.FilteringList.lists.list'):
            if rpz and ('enabled' in rpz) and (rpz['enabled'] == '1') and ('apply_to' in rpz) and rpz['apply_to']:
                for alias in rpz['apply_to'].split(','):
                    if alias:
                        result.append(alias)
        return result

    def hasRPZDefineTag(self):
        return not (not self._getRPZTags())

    def getRPZDefineTag(self):
        result = self._getRPZTags()
        return ' '.join(list(set(result)))

    def hasRPZApplyTo(self, rpz):
        return ('apply_to' in rpz) and (rpz['apply_to'])

    def getRPZApplyTo(self, rpz):
        if ('apply_to' in rpz) and (rpz['apply_to']):
            return ' '.join(rpz['apply_to'].split(','))
        return 'ALL'

    def aliasExists(self, name):
        for alias in self.getNodeAsList('OPNsense.Firewall.Alias.aliases.alias'):
            if alias['name'] == name:
                return True
        iface = self.getNodeByTag('interfaces.' + name)
        if iface:
            return True
        return False

    def getAliasContent(self, name):
        for alias in self.getNodeAsList('OPNsense.Firewall.Alias.aliases.alias'):
            if alias and 'name' in alias and alias['name'] == name:
                return alias['content']
        iface = self.getNodeByTag('interfaces.' + name)
        if iface:
            if 'subnet' in iface and iface['subnet'] and 'ipaddr' in iface and iface['ipaddr']:
                return iface['ipaddr'] + '/' + iface['subnet']
            if 'ipaddr' in iface and iface['ipaddr']:
                return iface['ipaddr']
        return '0.0.0.0'

    def getRPZAccessControlTags(self):
        act_dict = {}
        for alias in self._getRPZTags():
            content = self.getAliasContent(alias)
            if content not in act_dict:
                act_dict[content] = []
            act_dict[content].append(alias)
        return [ { 'net': content, 'tags': ' '.join(list(set(arr))) } for content, arr in act_dict.items() ]

    def getMinValue(self, tag, vb):
        node = self.getNodeByTag(tag)
        if node is None:
            return vb
        va = int(node)
        return va if va > vb else vb

    @staticmethod
    def getIPNetwork(network):
        """ generate network object using netaddr
            :param network: network
            :return: IPNetwork
        """
        return netaddr.IPNetwork(network)

    @staticmethod
    def sortDictList(lst, *operators):
        if type(lst) == list:
            lst.sort(key=SortKeyHelper(operators).get_key)
        elif type(lst) in (collections.OrderedDict, dict):
            return [lst]
        return lst

    @staticmethod
    def glob(pathname):
        """ glob within template directory scope
            :param pathname: relative path name
            :return: list
        """
        result = list()
        template_path = os.path.realpath("%s/../../templates/" % os.path.dirname(__file__))
        for sfilename in glob.glob("%s/%s" % (template_path, pathname)):
            sfilename = os.path.realpath(sfilename)
            if sfilename.startswith(template_path):
                result.append(sfilename[len(template_path):].lstrip('/'))

        return result
